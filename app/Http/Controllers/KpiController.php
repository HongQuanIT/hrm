<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Kpi;
use App\Models\KpiPhase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class KpiController extends Controller
{
    public function index()
    {
        // User thường chỉ thấy KPI mình được assign (chủ trì hoặc phụ trách giai đoạn).
        // Super Admin thấy tất cả.
        $baseQuery = $this->accessibleKpiQuery();

        $kpis = (clone $baseQuery)->with(['department', 'owner'])->latest()->get();

        $companyAvg = (int) round((clone $baseQuery)->avg('progress') ?? 0);

        $departmentAvg = (clone $baseQuery)
            ->selectRaw('department_id, AVG(progress) as avg_progress')
            ->groupBy('department_id')
            ->with('department')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->department?->name ?? 'Khác',
                'progress' => (int) round($row->avg_progress),
            ])
            ->take(4);

        $topKpi = (clone $baseQuery)->with('owner')->orderByDesc('progress')->first();

        $trend = [];
        for ($i = 6; $i >= 1; $i--) {
            $trend[] = ['label' => 'Th' . (7 - $i), 'pct' => min(100, 45 + $i * 7)];
        }

        return view('kpis.index', compact('kpis', 'companyAvg', 'departmentAvg', 'topKpi', 'trend'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('kpis.create', compact('departments', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $kpi = Kpi::create($data);

        $this->syncPhases($kpi, $request);

        return redirect()->route('kpis.show', $kpi)->with('status', 'Đã tạo mục tiêu KPI mới.');
    }

    public function show(Kpi $kpi)
    {
        $this->authorizeAccess($kpi);

        $kpi->load(['department', 'owner', 'phases.assignee']);
        $employees = Employee::orderBy('name')->get();

        return view('kpis.show', compact('kpi', 'employees'));
    }

    public function edit(Kpi $kpi)
    {
        $kpi->load('phases');
        $departments = Department::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('kpis.edit', compact('kpi', 'departments', 'employees'));
    }

    public function update(Request $request, Kpi $kpi)
    {
        $data = $this->validateData($request);
        $kpi->update($data);

        $this->syncPhases($kpi, $request);

        return redirect()->route('kpis.show', $kpi)->with('status', 'Đã cập nhật mục tiêu KPI.');
    }

    public function destroy(Kpi $kpi)
    {
        $kpi->delete();

        return redirect()->route('kpis.index')->with('status', 'Đã xóa mục tiêu KPI.');
    }

    /**
     * Thành viên dự án (chủ trì / người phụ trách giai đoạn) hoặc Super Admin
     * thêm một giai đoạn con mới cho KPI.
     */
    public function storePhase(Request $request, Kpi $kpi)
    {
        $this->authorizeAccess($kpi);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'assignee_employee_id' => ['nullable', 'exists:employees,id'],
            'deadline' => ['nullable', 'date'],
        ]);

        $kpi->phases()->create($validated + ['status' => KpiPhase::STATUS_PENDING]);

        $this->refreshKpiProgress($kpi);

        return back()->with('status', 'Đã thêm giai đoạn "' . $validated['name'] . '".');
    }

    /**
     * Người phụ trách (hoặc Super Admin) cập nhật trạng thái của một giai đoạn:
     * nhận việc -> đang làm -> hoàn thành. Có thể trả về trạng thái trước đó.
     */
    public function updatePhaseStatus(Request $request, Kpi $kpi, KpiPhase $phase)
    {
        $user = Auth::user();
        $employeeId = $user->employee?->id;
        $isAssignee = $employeeId && $phase->assignee_employee_id === $employeeId;

        abort_unless($user->isSuperAdmin() || $isAssignee, 403, 'Bạn không phụ trách giai đoạn này.');

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(KpiPhase::STATUS_LABELS))],
        ]);

        $status = $validated['status'];

        // Ghi lại mốc thời gian tương ứng, giữ nguyên mốc cũ nếu đã có.
        $updates = ['status' => $status];
        if ($status === KpiPhase::STATUS_RECEIVED && ! $phase->received_at) {
            $updates['received_at'] = now();
        }
        if ($status === KpiPhase::STATUS_IN_PROGRESS) {
            $updates['received_at'] = $phase->received_at ?? now();
            $updates['started_at'] = $phase->started_at ?? now();
        }
        if ($status === KpiPhase::STATUS_DONE) {
            $updates['received_at'] = $phase->received_at ?? now();
            $updates['started_at'] = $phase->started_at ?? now();
            $updates['completed_at'] = now();
        }
        // Nếu mở lại giai đoạn đã xong thì bỏ mốc hoàn thành.
        if ($status !== KpiPhase::STATUS_DONE) {
            $updates['completed_at'] = null;
        }

        $phase->update($updates);

        $this->refreshKpiProgress($kpi);

        return back()->with('status', 'Đã cập nhật giai đoạn "' . $phase->name . '": ' . $phase->status_label . '.');
    }

    /**
     * Query KPI theo quyền xem: Super Admin thấy tất cả; user thường chỉ thấy KPI
     * mình chủ trì hoặc được giao một giai đoạn (tức nằm trong dự án).
     */
    private function accessibleKpiQuery()
    {
        $user = Auth::user();
        $query = Kpi::query();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $employeeId = $user->employee?->id;

        if (! $employeeId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($employeeId) {
            $q->where('owner_employee_id', $employeeId)
                ->orWhereHas('phases', fn ($p) => $p->where('assignee_employee_id', $employeeId));
        });
    }

    /**
     * Chặn user thường xem/thao tác KPI không thuộc dự án của họ.
     */
    private function authorizeAccess(Kpi $kpi): void
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return;
        }

        $employeeId = $user->employee?->id;
        $isMember = $employeeId && (
            $kpi->owner_employee_id === $employeeId
            || $kpi->phases()->where('assignee_employee_id', $employeeId)->exists()
        );

        abort_unless($isMember, 403, 'Bạn không có quyền truy cập KPI này.');
    }

    /**
     * Tự động tính lại tiến độ KPI theo tỉ lệ giai đoạn đã hoàn thành.
     */
    private function refreshKpiProgress(Kpi $kpi): void
    {
        $total = $kpi->phases()->count();
        if ($total === 0) {
            return;
        }

        $done = $kpi->phases()->where('status', KpiPhase::STATUS_DONE)->count();
        $progress = (int) round($done / $total * 100);

        $status = $kpi->status;
        if ($done === $total) {
            $status = 'done';
        } elseif ($kpi->phases()->where('status', '!=', KpiPhase::STATUS_DONE)
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', now()->toDateString())
            ->exists()) {
            $status = 'behind';
        }

        $kpi->update(['progress' => $progress, 'status' => $status]);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'measure_type' => ['required', Rule::in(['percent', 'count', 'milestone'])],
            'unit' => ['nullable', 'string', 'max:50'],
            'target_value' => ['nullable', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'status' => ['required', Rule::in(['on_track', 'in_progress', 'behind', 'done'])],
            'deadline' => ['nullable', 'date'],
        ]);
    }

    private function syncPhases(Kpi $kpi, Request $request): void
    {
        $ids = $request->input('phase_id', []);
        $names = $request->input('phase_name', []);
        $assignees = $request->input('phase_assignee', []);
        $deadlines = $request->input('phase_deadline', []);

        $keptIds = [];

        foreach ($names as $i => $name) {
            if (! $name) {
                continue;
            }

            $attributes = [
                'name' => $name,
                'assignee_employee_id' => $assignees[$i] ?? null,
                'deadline' => $deadlines[$i] ?? null,
            ];

            $existingId = $ids[$i] ?? null;
            $phase = $existingId ? $kpi->phases()->find($existingId) : null;

            if ($phase) {
                // Giữ nguyên trạng thái & mốc thời gian mà người phụ trách đã cập nhật.
                $phase->update($attributes);
            } else {
                $phase = $kpi->phases()->create($attributes + ['status' => KpiPhase::STATUS_PENDING]);
            }

            $keptIds[] = $phase->id;
        }

        // Xoá các giai đoạn đã bị gỡ khỏi form.
        $kpi->phases()->whereNotIn('id', $keptIds ?: [0])->delete();
    }
}
