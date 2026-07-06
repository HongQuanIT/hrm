<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function index()
    {
        $isAdmin = auth()->user()->isSuperAdmin();
        $employee = $this->currentEmployee();
        $myEmployeeId = $employee?->id;

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthlyQuota = (int) CompanySetting::get('leave_days_per_month', 1);

        // Chỉ số dùng cho cả 2 chế độ (khởi tạo mặc định để view an toàn).
        $usedThisMonth = 0.0;
        $balance = 0.0;
        $approvedThisMonth = 0.0;
        $onLeaveToday = 0;
        $usedThisYear = 0.0;

        if ($isAdmin) {
            // Admin: tổng quan & phê duyệt toàn công ty.
            $pending = LeaveRequest::with('employee')
                ->where('status', 'pending')
                ->latest()
                ->get();

            $history = LeaveRequest::with('employee')
                ->whereIn('status', ['approved', 'rejected', 'cancelled'])
                ->latest('start_date')
                ->paginate(10);

            $approvedThisMonth = (float) LeaveRequest::where('status', 'approved')
                ->whereBetween('start_date', [$monthStart, $monthEnd])
                ->sum('days');

            $onLeaveToday = LeaveRequest::where('status', 'approved')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->distinct('employee_id')
                ->count('employee_id');

            $usedThisYear = (float) LeaveRequest::where('status', 'approved')
                ->whereYear('start_date', now()->year)
                ->sum('days');
        } else {
            // User: chỉ dữ liệu của chính mình, theo tháng hiện tại.
            $empId = $myEmployeeId ?? 0;

            $pending = LeaveRequest::with('employee')
                ->where('employee_id', $empId)
                ->where('status', 'pending')
                ->latest()
                ->get();

            $history = LeaveRequest::with('employee')
                ->where('employee_id', $empId)
                ->whereIn('status', ['approved', 'rejected', 'cancelled'])
                ->latest('start_date')
                ->paginate(10);

            // Quỹ phép tháng chỉ tính theo loại "Nghỉ phép tháng" (đã duyệt + đang chờ).
            $usedThisMonth = $this->monthlyUsedDays($empId, now());

            // Cho phép âm (nghỉ lố số ngày quy định).
            $balance = $monthlyQuota - $usedThisMonth;
        }

        $pendingCount = $pending->count();

        // Số dư cá nhân trong tháng (dùng cho form tạo đơn, áp dụng cho cả admin).
        $myUsedThisMonth = $employee ? $this->monthlyUsedDays($employee->id, now()) : 0.0;
        $myBalance = $monthlyQuota - $myUsedThisMonth;

        return view('leaves.index', compact(
            'isAdmin', 'pending', 'history', 'pendingCount', 'myEmployeeId',
            'monthlyQuota', 'usedThisMonth', 'balance',
            'approvedThisMonth', 'onLeaveToday', 'usedThisYear', 'myBalance'
        ));
    }

    public function calendar(Request $request)
    {
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;
        $current = Carbon::createFromDate($year, $month, 1);

        $isAdmin = auth()->user()->isSuperAdmin();
        $employee = $this->currentEmployee();

        $leaves = LeaveRequest::with('employee')
            // User chỉ thấy lịch nghỉ của chính mình.
            ->when(! $isAdmin, fn ($q) => $q->where('employee_id', $employee?->id ?? 0))
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($q) use ($current) {
                $q->whereBetween('start_date', [$current->copy()->startOfMonth(), $current->copy()->endOfMonth()])
                    ->orWhereBetween('end_date', [$current->copy()->startOfMonth(), $current->copy()->endOfMonth()]);
            })
            ->get();

        // Build per-day map
        $byDay = [];
        foreach ($leaves as $leave) {
            $cursor = $leave->start_date->copy();
            while ($cursor->lte($leave->end_date)) {
                if ($cursor->month === $current->month && $cursor->year === $current->year) {
                    $byDay[$cursor->day][] = $leave;
                }
                $cursor->addDay();
            }
        }

        // Build calendar grid (Monday-start)
        $firstDay = $current->copy()->startOfMonth();
        $daysInMonth = $current->daysInMonth;
        $leadingBlanks = ($firstDay->dayOfWeekIso - 1); // 0..6
        $cells = [];
        for ($i = 0; $i < $leadingBlanks; $i++) {
            $cells[] = null;
        }
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $cells[] = [
                'day' => $d,
                'leaves' => $byDay[$d] ?? [],
                'is_today' => $current->copy()->day($d)->isToday(),
            ];
        }
        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return view('leaves.calendar', [
            'cells' => $cells,
            'current' => $current,
            'prev' => $current->copy()->subMonth(),
            'next' => $current->copy()->addMonth(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(LeaveRequest::TYPE_LABELS))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $days = (int) round($start->diffInDays($end, true)) + 1;
        $reason = $data['reason'] ?? null;

        $employee = $this->currentEmployee() ?? Employee::first();
        $employeeId = $employee?->id ?? 1;

        // Nghỉ phép tháng: nếu vượt quá quỹ phép tháng còn lại thì phần vượt
        // sẽ được ghi nhận là Nghỉ không lương.
        if ($data['type'] === 'monthly') {
            return $this->storeMonthlyLeave($employeeId, $start, $end, $days, $reason);
        }

        LeaveRequest::create([
            'employee_id' => $employeeId,
            'type' => $data['type'],
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => $days,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        return redirect()->route('leaves.index')->with('status', 'Đã gửi đơn nghỉ phép, đang chờ phê duyệt.');
    }

    /**
     * Xử lý đơn "Nghỉ phép tháng": tách phần còn quỹ (monthly) và phần vượt (unpaid).
     */
    private function storeMonthlyLeave(int $employeeId, Carbon $start, Carbon $end, int $days, ?string $reason)
    {
        $quota = (int) CompanySetting::get('leave_days_per_month', 1);
        $used = $this->monthlyUsedDays($employeeId, $start);
        $remaining = (int) floor(max($quota - $used, 0));

        // Còn đủ quỹ: ghi nhận toàn bộ là nghỉ phép tháng.
        if ($remaining >= $days) {
            LeaveRequest::create([
                'employee_id' => $employeeId,
                'type' => 'monthly',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days' => $days,
                'reason' => $reason,
                'status' => 'pending',
            ]);

            return redirect()->route('leaves.index')->with('status', 'Đã gửi đơn nghỉ phép tháng, đang chờ phê duyệt.');
        }

        // Hết quỹ: toàn bộ tính nghỉ không lương.
        if ($remaining <= 0) {
            LeaveRequest::create([
                'employee_id' => $employeeId,
                'type' => 'unpaid',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days' => $days,
                'reason' => $reason,
                'status' => 'pending',
            ]);

            return redirect()->route('leaves.index')->with('status', 'Bạn đã dùng hết quỹ phép tháng (' . $quota . ' ngày) nên đơn này được ghi nhận là Nghỉ không lương, đang chờ phê duyệt.');
        }

        // Vượt một phần: tách monthly (phần còn quỹ) + unpaid (phần vượt).
        $monthlyEnd = $start->copy()->addDays($remaining - 1);
        $unpaidStart = $monthlyEnd->copy()->addDay();
        $unpaidDays = $days - $remaining;

        LeaveRequest::create([
            'employee_id' => $employeeId,
            'type' => 'monthly',
            'start_date' => $start->toDateString(),
            'end_date' => $monthlyEnd->toDateString(),
            'days' => $remaining,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        LeaveRequest::create([
            'employee_id' => $employeeId,
            'type' => 'unpaid',
            'start_date' => $unpaidStart->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => $unpaidDays,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        return redirect()->route('leaves.index')->with('status', 'Đơn được tách: ' . $remaining . ' ngày tính Nghỉ phép tháng, ' . $unpaidDays . ' ngày còn lại tính Nghỉ không lương (đang chờ phê duyệt).');
    }

    /**
     * Số ngày phép tháng đã đăng ký (đã duyệt + đang chờ) trong tháng của ngày tham chiếu.
     */
    private function monthlyUsedDays(int $employeeId, Carbon $ref): float
    {
        return (float) LeaveRequest::where('employee_id', $employeeId)
            ->where('type', 'monthly')
            ->whereIn('status', ['approved', 'pending'])
            ->whereYear('start_date', $ref->year)
            ->whereMonth('start_date', $ref->month)
            ->sum('days');
    }

    public function updateStatus(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'cancelled'])],
        ]);

        $leave->status = $data['status'];
        if (in_array($data['status'], ['approved', 'rejected'])) {
            $leave->approver_name = auth()->user()->name;
        }
        $leave->save();

        return back()->with('status', 'Đã cập nhật trạng thái đơn nghỉ phép.');
    }

    public function cancel(LeaveRequest $leave)
    {
        $employee = $this->currentEmployee();

        // Chỉ được huỷ đơn của chính mình và khi đơn còn đang chờ duyệt.
        abort_unless(
            $employee && $leave->employee_id === $employee->id && $leave->status === 'pending',
            403,
            'Bạn chỉ có thể huỷ đơn nghỉ của chính mình khi đang chờ duyệt.'
        );

        $leave->update(['status' => 'cancelled']);

        return back()->with('status', 'Đã huỷ đơn nghỉ phép của bạn.');
    }

    public function destroy(LeaveRequest $leave)
    {
        $leave->delete();

        return back()->with('status', 'Đã hủy đơn nghỉ phép.');
    }

    private function currentEmployee(): ?Employee
    {
        return Employee::where('email', auth()->user()->email)->first();
    }
}
