<?php

namespace App\Http\Controllers;

use App\Http\Requests\KpiRequest;
use App\Models\Attachment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Kpi;
use App\Models\KpiPhase;
use App\Models\PhaseChecklistItem;
use App\Models\PhaseComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class KpiController extends Controller
{
    public function index()
    {
        // User thường chỉ thấy KPI mình được assign (chủ trì hoặc phụ trách giai đoạn).
        // Super Admin thấy tất cả.
        $baseQuery = $this->accessibleKpiQuery();

        // F12: phân trang danh sách KPI thay vì lấy toàn bộ.
        $kpis = (clone $baseQuery)->with(['department', 'owner'])->latest()->paginate(10);

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

        // F03: xu hướng thật = số giai đoạn hoàn thành theo từng tháng (6 tháng gần nhất),
        // trong phạm vi KPI mà người dùng được xem. Thay cho dữ liệu giả trước đây.
        $accessibleKpiIds = (clone $baseQuery)->pluck('id');
        $trend = [];
        $trendMax = 1;
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = KpiPhase::whereIn('kpi_id', $accessibleKpiIds)
                ->whereNotNull('completed_at')
                ->whereYear('completed_at', $month->year)
                ->whereMonth('completed_at', $month->month)
                ->count();
            $trendMax = max($trendMax, $count);
            $trend[] = ['label' => 'Th' . $month->format('m'), 'count' => $count];
        }
        foreach ($trend as &$t) {
            $t['pct'] = (int) round(($t['count'] / $trendMax) * 100);
        }
        unset($t);

        return view('kpis.index', compact('kpis', 'companyAvg', 'departmentAvg', 'topKpi', 'trend'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('kpis.create', compact('departments', 'employees'));
    }

    public function store(KpiRequest $request)
    {
        $kpi = Kpi::create($this->cleanKpiData($request->validated()));

        $this->saveUploadedAttachments($kpi, $request);
        // F08: khi KPI có giai đoạn, tiến độ do hệ thống tính (bỏ giá trị nhập tay để tránh mâu thuẫn).
        $this->refreshKpiProgress($kpi);

        return redirect()->route('kpis.show', $kpi)->with('status', 'Đã tạo mục tiêu KPI mới.');
    }

    public function show(Kpi $kpi)
    {
        $this->authorizeAccess($kpi);

        $kpi->load([
            'department', 'owner', 'attachments.uploader',
            'phases.assignee', 'phases.checklistItems', 'phases.comments.user',
        ]);
        $employees = Employee::orderBy('name')->get();

        return view('kpis.show', compact('kpi', 'employees'));
    }

    public function edit(Kpi $kpi)
    {
        $kpi->load(['phases', 'attachments.uploader']);
        $departments = Department::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('kpis.edit', compact('kpi', 'departments', 'employees'));
    }

    public function update(KpiRequest $request, Kpi $kpi)
    {
        $kpi->update($this->cleanKpiData($request->validated()));

        $this->saveUploadedAttachments($kpi, $request);
        // F08: tiến độ do hệ thống tính lại theo giai đoạn sau khi đồng bộ.
        $this->refreshKpiProgress($kpi);

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
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', Rule::in(array_keys(KpiPhase::PRIORITY_LABELS))],
            'assignee_employee_id' => ['nullable', 'exists:employees,id'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
        ]);

        if (! empty($validated['description'])) {
            $validated['description'] = clean($validated['description']);
        }
        $validated['priority'] = $validated['priority'] ?? 'medium';

        $kpi->phases()->create($validated + ['status' => KpiPhase::STATUS_PENDING]);

        $this->refreshKpiProgress($kpi);

        return back()->with('status', 'Đã thêm giai đoạn "' . $validated['name'] . '".');
    }

    /**
     * Chỉnh sửa thông tin một giai đoạn ngay trong drawer trên trang chi tiết.
     * Không đụng tới trạng thái/mốc thời gian (do luồng Kanban quản lý riêng).
     */
    public function updatePhase(Request $request, Kpi $kpi, KpiPhase $phase)
    {
        $this->authorizePhaseAction($kpi, $phase);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', Rule::in(array_keys(KpiPhase::PRIORITY_LABELS))],
            'assignee_employee_id' => ['nullable', 'exists:employees,id'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
        ]);

        $validated['description'] = ! empty($validated['description']) ? clean($validated['description']) : null;
        $validated['priority'] = $validated['priority'] ?? 'medium';

        $phase->update($validated);

        $this->refreshKpiProgress($kpi);

        return $this->backToPhase($phase);
    }

    /**
     * Xoá một giai đoạn (soft delete). Chỉ Super Admin, có xác nhận ở giao diện.
     */
    public function destroyPhase(Kpi $kpi, KpiPhase $phase)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
        abort_unless($phase->kpi_id === $kpi->id, 404);

        $name = $phase->name;
        $phase->delete();

        $this->refreshKpiProgress($kpi);

        return redirect()->route('kpis.show', $kpi)->with('status', 'Đã xoá giai đoạn "' . $name . '".');
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

        // Nếu gọi bằng fetch (kéo-thả Kanban) thì trả JSON để JS xử lý; ngược lại quay về và mở drawer.
        if ($request->expectsJson() || $request->boolean('ajax')) {
            return response()->json(['ok' => true, 'status' => $phase->status, 'progress' => $kpi->fresh()->progress]);
        }

        return redirect()->route('kpis.show', $kpi)
            ->with('status', 'Đã cập nhật giai đoạn "' . $phase->name . '": ' . $phase->status_label . '.')
            ->with('open_phase', $phase->id);
    }

    /**
     * Thêm mục checklist cho giai đoạn (assignee hoặc admin).
     */
    public function addChecklistItem(Request $request, Kpi $kpi, KpiPhase $phase)
    {
        $this->authorizePhaseAction($kpi, $phase);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $phase->checklistItems()->create([
            'title' => $validated['title'],
            'position' => (int) $phase->checklistItems()->max('position') + 1,
        ]);

        return $this->backToPhase($phase);
    }

    /**
     * Bật/tắt trạng thái hoàn thành của một mục checklist.
     */
    public function toggleChecklistItem(Kpi $kpi, KpiPhase $phase, PhaseChecklistItem $item)
    {
        $this->authorizePhaseAction($kpi, $phase);
        abort_unless($item->kpi_phase_id === $phase->id, 404);

        $item->update(['is_done' => ! $item->is_done]);

        return $this->backToPhase($phase);
    }

    /**
     * Xoá một mục checklist.
     */
    public function deleteChecklistItem(Kpi $kpi, KpiPhase $phase, PhaseChecklistItem $item)
    {
        $this->authorizePhaseAction($kpi, $phase);
        abort_unless($item->kpi_phase_id === $phase->id, 404);

        $item->delete();

        return $this->backToPhase($phase);
    }

    /**
     * Thêm bình luận vào giai đoạn (mọi thành viên KPI hoặc admin).
     */
    public function addComment(Request $request, Kpi $kpi, KpiPhase $phase)
    {
        $this->authorizeAccess($kpi);
        abort_unless($phase->kpi_id === $kpi->id, 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $phase->comments()->create([
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        return $this->backToPhase($phase);
    }

    /**
     * Chỉ assignee của giai đoạn hoặc Super Admin mới được sửa checklist.
     */
    private function authorizePhaseAction(Kpi $kpi, KpiPhase $phase): void
    {
        abort_unless($phase->kpi_id === $kpi->id, 404);

        $user = Auth::user();
        $employeeId = $user->employee?->id;
        $isAssignee = $employeeId && $phase->assignee_employee_id === $employeeId;

        abort_unless($user->isSuperAdmin() || $isAssignee, 403, 'Bạn không phụ trách giai đoạn này.');
    }

    /**
     * Quay lại trang KPI và mở lại drawer của giai đoạn vừa thao tác.
     */
    private function backToPhase(KpiPhase $phase)
    {
        return redirect()
            ->route('kpis.show', $phase->kpi_id)
            ->with('open_phase', $phase->id);
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

    /**
     * Tải tài liệu đính kèm cho KPI hoặc một giai đoạn con của KPI.
     */
    public function storeAttachment(Request $request, Kpi $kpi)
    {
        $this->authorizeAccess($kpi);

        $validated = $request->validate([
            'file' => [
                'required', 'file', 'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,jpg,jpeg,png,gif,webp,zip,rar',
            ],
            'phase_id' => ['nullable', 'integer'],
        ]);

        // Mặc định đính kèm vào KPI; nếu có phase_id hợp lệ thì gắn vào giai đoạn.
        $target = $kpi;
        if (! empty($validated['phase_id'])) {
            $phase = $kpi->phases()->find($validated['phase_id']);
            if ($phase) {
                $target = $phase;
            }
        }

        $file = $request->file('file');
        $path = $file->store('attachments/kpi/' . $kpi->id, 'public');

        $target->attachments()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('status', 'Đã tải lên tài liệu "' . $file->getClientOriginalName() . '".');
    }

    /**
     * Xoá tài liệu đính kèm (phải thuộc KPI hoặc giai đoạn con của KPI này).
     */
    public function destroyAttachment(Kpi $kpi, Attachment $attachment)
    {
        $this->authorizeAccess($kpi);

        $belongsToKpi = $attachment->attachable_type === Kpi::class
            && (int) $attachment->attachable_id === $kpi->id;
        $belongsToPhase = $attachment->attachable_type === KpiPhase::class
            && $kpi->phases()->whereKey($attachment->attachable_id)->exists();

        abort_unless($belongsToKpi || $belongsToPhase, 404);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return back()->with('status', 'Đã xoá tài liệu đính kèm.');
    }

    /**
     * Làm sạch HTML mô tả (chống XSS) trước khi lưu và bỏ trường không thuộc bảng kpis.
     */
    private function cleanKpiData(array $data): array
    {
        unset($data['attachments']);

        if (! empty($data['description'])) {
            $data['description'] = clean($data['description']);
        }

        return $data;
    }

    /**
     * Lưu các tệp tải lên (nếu có) kèm KPI khi tạo/cập nhật.
     */
    private function saveUploadedAttachments(Kpi $kpi, Request $request): void
    {
        foreach ((array) $request->file('attachments', []) as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('attachments/kpi/' . $kpi->id, 'public');

            $kpi->attachments()->create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);
        }
    }

}
