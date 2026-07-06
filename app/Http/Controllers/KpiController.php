<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Kpi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KpiController extends Controller
{
    public function index()
    {
        $kpis = Kpi::with(['department', 'owner'])->latest()->get();

        $companyAvg = (int) round(Kpi::avg('progress') ?? 0);

        $departmentAvg = Kpi::selectRaw('department_id, AVG(progress) as avg_progress')
            ->groupBy('department_id')
            ->with('department')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->department?->name ?? 'Khác',
                'progress' => (int) round($row->avg_progress),
            ])
            ->take(4);

        $topPerformer = Employee::withCount([])
            ->whereHas('department')
            ->orderBy('name')
            ->first();
        $topKpi = Kpi::with('owner')->orderByDesc('progress')->first();

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
        $kpi->load(['department', 'owner', 'phases.assignee']);

        return view('kpis.show', compact('kpi'));
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

        $kpi->phases()->delete();
        $this->syncPhases($kpi, $request);

        return redirect()->route('kpis.show', $kpi)->with('status', 'Đã cập nhật mục tiêu KPI.');
    }

    public function destroy(Kpi $kpi)
    {
        $kpi->delete();

        return redirect()->route('kpis.index')->with('status', 'Đã xóa mục tiêu KPI.');
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
        $names = $request->input('phase_name', []);
        $assignees = $request->input('phase_assignee', []);
        $deadlines = $request->input('phase_deadline', []);
        $statuses = $request->input('phase_status', []);

        foreach ($names as $i => $name) {
            if (! $name) {
                continue;
            }
            $kpi->phases()->create([
                'name' => $name,
                'assignee_employee_id' => $assignees[$i] ?? null,
                'deadline' => $deadlines[$i] ?? null,
                'status' => $statuses[$i] ?? 'pending',
            ]);
        }
    }
}
