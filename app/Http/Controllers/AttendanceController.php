<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Services\AttendanceCloser;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request, AttendanceCloser $closer)
    {
        // F09: tự động chốt công nhưng có khoá throttle để không lặp truy vấn mỗi lần tải trang.
        $closer->runThrottled();

        $currentEmployee = $this->currentEmployee();
        $isAdmin = auth()->user()->isSuperAdmin();

        // Super Admin xem lịch sử toàn bộ nhân viên; user chỉ xem của mình.
        // Nếu tài khoản không (còn) gắn hồ sơ nhân viên, dùng id 0 để không lộ dữ liệu người khác.
        $ownId = $currentEmployee?->id ?? 0;

        // Tháng đang xem (mặc định tháng hiện tại, không cho chọn tương lai).
        $selected = $this->selectedMonth($request);
        $monthStart = $selected->copy()->startOfMonth();
        $monthEnd = $selected->copy()->endOfMonth();

        // Admin có thể lọc bảng lịch sử theo một nhân viên cụ thể (bấm từ bảng tổng kết).
        $filterEmployeeId = $isAdmin ? (int) $request->query('nhan_vien') : 0;
        $filteredEmployee = $filterEmployeeId > 0 ? Employee::find($filterEmployeeId) : null;

        // Lịch sử chỉ hiển thị trong tháng đang xem.
        $records = Attendance::with('employee')
            ->when(! $isAdmin, fn ($q) => $q->where('employee_id', $ownId))
            ->when($filteredEmployee, fn ($q) => $q->where('employee_id', $filteredEmployee->id))
            ->whereBetween('work_date', [$monthStart, $monthEnd])
            ->orderByDesc('work_date')
            ->paginate(15)
            ->withQueryString();

        // Cấu hình chính sách nghỉ phép.
        $leavePerMonth = (int) CompanySetting::get('leave_days_per_month', 1);
        $leavePerYear = (int) CompanySetting::get('leave_days_per_year', 12);

        // Chỉ số cá nhân tính theo tháng đang xem, dựa trên hồ sơ người đăng nhập.
        $employee = $currentEmployee;
        $baseQuery = Attendance::query()
            ->where('employee_id', $ownId)
            ->whereBetween('work_date', [$monthStart, $monthEnd]);

        $workedDays = (clone $baseQuery)->whereIn('status', ['on_time', 'late', 'working'])->count();
        $lateCount = (clone $baseQuery)->where('status', 'late')->count();
        $overtimeHours = round((clone $baseQuery)->sum('total_minutes') / 60 - $workedDays * 8, 1);
        $overtimeHours = max($overtimeHours, 0);

        // Ngày công chuẩn = số ngày trong tháng - số ngày phép tháng - số ngày nghỉ lễ trong tháng (F15).
        $holidaysInMonth = Holiday::whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])->count();
        $standardDays = max($selected->daysInMonth - $leavePerMonth - $holidaysInMonth, 0);

        // Số dư phép năm = quỹ phép năm - số ngày phép đã duyệt trong năm.
        $leaveUsedYear = $employee
            ? (float) LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $selected->year)
                ->sum('days')
            : 0;
        $leaveBalance = max($leavePerYear - $leaveUsedYear, 0);

        $todayRecord = null;
        $todayOnLeave = false;
        if ($employee) {
            $todayRecord = Attendance::where('employee_id', $employee->id)
                ->whereDate('work_date', Carbon::today())
                ->first();
            $todayOnLeave = $this->onApprovedLeave($employee, Carbon::today());
        }

        // Mốc giờ để hiển thị trên thẻ chấm công.
        $workStart = (string) CompanySetting::get('work_start_time', '08:00');
        $workEnd = (string) CompanySetting::get('work_end_time', '17:30');
        $checkinOpen = (string) CompanySetting::get('checkin_open_time', '07:00');
        $checkinDeadline = (string) CompanySetting::get('checkin_deadline', '10:00');

        // 6-month trend (kết thúc ở tháng đang xem)
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $selected->copy()->subMonths($i);
            $count = Attendance::query()
                ->where('employee_id', $ownId)
                ->whereMonth('work_date', $month->month)
                ->whereYear('work_date', $month->year)
                ->whereIn('status', ['on_time', 'late', 'working'])
                ->count();
            $trend[] = ['label' => 'Tháng ' . $month->format('m'), 'count' => $count];
        }
        $trendMax = max(array_column($trend, 'count')) ?: 1;
        foreach ($trend as &$t) {
            $t['pct'] = (int) round(($t['count'] / $trendMax) * 100);
        }
        unset($t);

        // Bảng tổng kết công tháng cho từng nhân viên (chỉ Admin), có tìm kiếm + lọc phòng ban.
        $employeeSummaries = collect();
        $departments = collect();
        $summarySearch = '';
        $summaryDept = 0;
        if ($isAdmin) {
            $departments = Department::orderBy('name')->get();
            $summarySearch = trim((string) $request->query('q'));
            $summaryDept = (int) $request->query('phong_ban');

            // Gộp số liệu chấm công của tháng theo từng nhân viên trong một truy vấn.
            $agg = Attendance::query()
                ->whereBetween('work_date', [$monthStart, $monthEnd])
                ->selectRaw('employee_id')
                ->selectRaw("SUM(CASE WHEN status IN ('on_time','late','working') THEN 1 ELSE 0 END) as worked_days")
                ->selectRaw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days")
                ->selectRaw('SUM(late_minutes) as late_minutes_total')
                ->selectRaw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days")
                ->selectRaw("SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days")
                ->selectRaw('SUM(total_minutes) as total_minutes')
                ->groupBy('employee_id')
                ->get()
                ->keyBy('employee_id');

            $employees = Employee::with('department')
                ->when($summarySearch !== '', fn ($q) => $q->where(function ($qq) use ($summarySearch) {
                    $qq->where('name', 'like', "%{$summarySearch}%")
                        ->orWhere('code', 'like', "%{$summarySearch}%");
                }))
                ->when($summaryDept > 0, fn ($q) => $q->where('department_id', $summaryDept))
                ->orderBy('name')
                ->get();

            $employeeSummaries = $employees->map(function (Employee $emp) use ($agg) {
                $row = $agg->get($emp->id);
                $workedDays = (int) ($row->worked_days ?? 0);
                $totalMinutes = (int) ($row->total_minutes ?? 0);
                $overtime = round($totalMinutes / 60 - $workedDays * 8, 1);

                return [
                    'employee' => $emp,
                    'worked_days' => $workedDays,
                    'late_days' => (int) ($row->late_days ?? 0),
                    'late_minutes_total' => (int) ($row->late_minutes_total ?? 0),
                    'absent_days' => (int) ($row->absent_days ?? 0),
                    'leave_days' => (int) ($row->leave_days ?? 0),
                    'total_minutes' => $totalMinutes,
                    'overtime_hours' => max($overtime, 0),
                ];
            });
        }

        $showAllHistory = $isAdmin;
        $selectedValue = $selected->format('Y-m');
        $selectedLabel = $selected->translatedFormat('\T\h\á\n\g m/Y');
        $currentMonthValue = Carbon::today()->format('Y-m');

        return view('attendance.index', compact(
            'records', 'workedDays', 'standardDays', 'lateCount',
            'overtimeHours', 'leaveBalance', 'todayRecord', 'employee', 'trend', 'showAllHistory',
            'selectedValue', 'selectedLabel', 'currentMonthValue',
            'todayOnLeave', 'workStart', 'workEnd', 'checkinOpen', 'checkinDeadline',
            'employeeSummaries', 'departments', 'summarySearch', 'summaryDept', 'filteredEmployee'
        ));
    }

    private function selectedMonth(Request $request): Carbon
    {
        $current = Carbon::today()->startOfMonth();
        $input = (string) $request->query('thang');

        if (preg_match('/^\d{4}-\d{2}$/', $input)) {
            try {
                $parsed = Carbon::createFromFormat('Y-m-d', $input . '-01')->startOfMonth();
                if ($parsed->lessThanOrEqualTo($current)) {
                    return $parsed;
                }
            } catch (\Throwable $e) {
                // giữ mặc định tháng hiện tại
            }
        }

        return $current;
    }

    public function checkin(Request $request)
    {
        $employee = $this->currentEmployee();
        if (! $employee) {
            return back()->with('error', 'Tài khoản chưa gắn với hồ sơ nhân viên để chấm công.');
        }

        $today = Carbon::today();
        $now = Carbon::now();

        // Ngày nghỉ đã được duyệt: không cần chấm công.
        if ($this->onApprovedLeave($employee, $today)) {
            return back()->with('error', 'Hôm nay bạn đang trong kỳ nghỉ phép đã được duyệt, không cần chấm công.');
        }

        // Chưa tới giờ mở check-in.
        $openTime = $this->timeOn($today, 'checkin_open_time', '07:00');
        if ($now->lessThan($openTime)) {
            return back()->with('error', 'Chưa đến giờ mở check-in (' . $openTime->format('H:i') . ').');
        }

        $record = Attendance::firstOrNew([
            'employee_id' => $employee->id,
            'work_date' => $today->toDateString(),
        ]);

        if ($record->check_in) {
            return back()->with('error', 'Bạn đã check in hôm nay rồi.');
        }

        $start = $this->timeOn($today, 'work_start_time', '08:00');
        $graceLimit = $start->copy()->addMinutes((int) CompanySetting::get('late_grace_minutes', 5));
        $deadline = $this->timeOn($today, 'checkin_deadline', '10:00');

        $lateMinutes = $now->greaterThan($start) ? (int) round($start->diffInMinutes($now, true)) : 0;
        $record->check_in = $now->format('H:i:s');
        $record->late_minutes = $lateMinutes;

        // Quá hạn check-in: coi như vắng mặt.
        if ($now->greaterThan($deadline)) {
            $record->status = 'absent';
            $record->note = 'Check-in lúc ' . $now->format('H:i') . ' (sau hạn chót ' . $deadline->format('H:i') . ') nên tính là vắng mặt.';
            $record->save();

            return back()->with('error', 'Bạn check-in lúc ' . $now->format('H:i') . ' — muộn quá hạn chót ' . $deadline->format('H:i') . ' nên hôm nay được tính là Vắng mặt.');
        }

        // Đi muộn (ngoài khoảng ân hạn).
        if ($now->greaterThan($graceLimit)) {
            $record->status = 'late';
            $record->note = null;
            $record->save();

            return back()->with('status', 'Check in lúc ' . $now->format('H:i') . ' — đi muộn ' . $lateMinutes . ' phút.');
        }

        // Đúng giờ / trong ân hạn.
        $record->status = 'working';
        $record->late_minutes = 0;
        $record->note = null;
        $record->save();

        return back()->with('status', 'Check in thành công lúc ' . $now->format('H:i') . '.');
    }

    public function checkout(Request $request)
    {
        $employee = $this->currentEmployee();
        if (! $employee) {
            return back()->with('error', 'Tài khoản chưa gắn với hồ sơ nhân viên để chấm công.');
        }

        $record = Attendance::where('employee_id', $employee->id)
            ->whereDate('work_date', Carbon::today())
            ->first();

        if (! $record || ! $record->check_in) {
            return back()->with('error', 'Bạn chưa check in hôm nay.');
        }

        if ($record->check_out) {
            return back()->with('error', 'Bạn đã check out hôm nay rồi.');
        }

        $today = $record->work_date->copy();
        $now = Carbon::now();

        // Quên bấm ra: tự chốt giờ ra về mốc hạn chót check-out.
        $checkoutDeadline = $this->timeOn($today, 'checkout_deadline', '22:00');
        $effective = $now->greaterThan($checkoutDeadline) ? $checkoutDeadline : $now;
        $capped = $now->greaterThan($checkoutDeadline);

        $checkIn = Carbon::parse($today->toDateString() . ' ' . $record->check_in);
        $record->check_out = $effective->format('H:i:s');
        $record->total_minutes = max((int) round($checkIn->diffInMinutes($effective, true)), 0);

        // Giữ nguyên trạng thái late/absent; chỉ chốt "working" thành "on_time".
        if ($record->status === 'working') {
            $record->status = 'on_time';
        }

        // Cảnh báo về sớm so với giờ kết thúc chuẩn.
        $workEnd = $this->timeOn($today, 'work_end_time', '17:30');
        $earlyLeave = $effective->lessThan($workEnd);
        if ($earlyLeave) {
            $record->note = trim(($record->note ? $record->note . ' ' : '') . 'Về sớm ' . (int) round($effective->diffInMinutes($workEnd, true)) . ' phút so với giờ kết thúc.');
        }

        $record->save();

        if ($capped) {
            return back()->with('status', 'Bạn quên bấm ra; hệ thống tự chốt giờ ra về ' . $checkoutDeadline->format('H:i') . '.');
        }

        return back()->with('status', 'Check out thành công lúc ' . $effective->format('H:i') . ($earlyLeave ? ' (về sớm).' : '.'));
    }

    private function currentEmployee(): ?Employee
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        return $user->employee ?? Employee::where('email', $user->email)->first();
    }

    private function onApprovedLeave(Employee $employee, Carbon $date): bool
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    private function timeOn(Carbon $date, string $key, string $default): Carbon
    {
        $value = (string) CompanySetting::get($key, $default);
        [$hour, $minute] = array_pad(explode(':', $value), 2, '0');

        return $date->copy()->setTime((int) $hour, (int) $minute, 0);
    }
}
