<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Kpi;
use App\Models\LeaveRequest;
use App\Services\FinanceService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(\App\Services\AttendanceCloser $closer)
    {
        // F09: đồng bộ trạng thái vắng mặt (có throttle) trước khi hiển thị số liệu.
        $closer->runThrottled();

        if (auth()->user()->isSuperAdmin()) {
            return $this->adminDashboard();
        }

        return $this->personalDashboard();
    }

    private function adminDashboard()
    {
        $today = Carbon::today();

        $totalEmployees = Employee::count();
        // F02: "đang nghỉ hôm nay" lấy từ đơn nghỉ đã duyệt trùng ngày hôm nay
        // để đồng nhất với module Nghỉ phép (không dùng employees.status).
        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->distinct('employee_id')
            ->count('employee_id');
        $lateToday = Attendance::whereDate('work_date', $today)->where('status', 'late')->count();
        $workingToday = max($totalEmployees - $onLeaveToday, 0);
        $avgKpi = (int) round(Kpi::avg('progress') ?? 0);

        // F13: 7-day attendance bằng MỘT truy vấn gộp thay vì 7 truy vấn.
        $labels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->addDays(6);
        $countsByDate = Attendance::whereBetween('work_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->whereIn('status', ['on_time', 'late', 'working'])
            ->selectRaw('DATE(work_date) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $weekAttendance = [];
        $maxCount = 1;
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $count = (int) ($countsByDate[$date->toDateString()] ?? 0);
            $maxCount = max($maxCount, $count);
            $weekAttendance[] = [
                'label' => $labels[$i],
                'count' => $count,
                'is_today' => $date->isSameDay($today),
                'is_weekend' => $i >= 5,
            ];
        }
        foreach ($weekAttendance as &$day) {
            $day['pct'] = (int) round(($day['count'] / $maxCount) * 100);
        }
        unset($day);

        // Leave ratio (this month)
        $leaveUsed = (float) LeaveRequest::where('status', 'approved')
            ->whereMonth('start_date', $today->month)
            ->whereYear('start_date', $today->year)
            ->sum('days');
        $leaveQuota = max($totalEmployees * 12, 1);
        $leavePct = (int) round(($leaveUsed / $leaveQuota) * 100);

        $newEmployees = Employee::with('department')
            ->latest('join_date')
            ->take(3)
            ->get();

        $pendingLeaves = LeaveRequest::where('status', 'pending')->count();

        $recentLeaves = LeaveRequest::with('employee')
            ->latest()
            ->take(3)
            ->get();

        // M10 (FR-M10-20): số dư hiện có + tổng nạp/chi cho thẻ tài chính.
        $finance = app(FinanceService::class)->summary();

        return view('dashboard', compact(
            'totalEmployees', 'workingToday', 'onLeaveToday', 'lateToday', 'avgKpi',
            'weekAttendance', 'leaveUsed', 'leaveQuota', 'leavePct',
            'newEmployees', 'pendingLeaves', 'recentLeaves', 'finance'
        ));
    }

    private function personalDashboard()
    {
        $user = auth()->user();
        // F04: nhận diện nhân viên nhất quán với các controller khác (ưu tiên quan hệ user_id).
        $employee = $this->currentEmployee();
        $employee?->loadMissing('department');

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $leavePerMonth = (int) CompanySetting::get('leave_days_per_month', 1);

        // Mặc định an toàn khi tài khoản chưa gắn hồ sơ nhân viên.
        $todayRecord = null;
        $workedDays = 0;
        // F15: ngày công chuẩn trừ thêm ngày nghỉ lễ trong tháng.
        $holidaysInMonth = \App\Models\Holiday::whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])->count();
        $standardDays = max($today->daysInMonth - $leavePerMonth - $holidaysInMonth, 0);
        $lateThisMonth = 0;
        $usedThisMonth = 0.0;
        $leaveBalance = (float) $leavePerMonth;
        $myKpis = collect();
        $myAvgKpi = 0;
        $weekAttendance = [];
        $recentLeaves = collect();
        $pendingCount = 0;

        if ($employee) {
            $todayRecord = Attendance::where('employee_id', $employee->id)
                ->whereDate('work_date', $today)
                ->first();

            $monthQuery = Attendance::where('employee_id', $employee->id)
                ->whereBetween('work_date', [$monthStart, $monthEnd]);
            $workedDays = (clone $monthQuery)->whereIn('status', ['on_time', 'late', 'working'])->count();
            $lateThisMonth = (clone $monthQuery)->where('status', 'late')->count();

            // Quỹ phép tháng tính theo loại "Nghỉ phép tháng" (đã duyệt + đang chờ).
            $usedThisMonth = (float) LeaveRequest::where('employee_id', $employee->id)
                ->where('type', 'monthly')
                ->whereIn('status', ['approved', 'pending'])
                ->whereBetween('start_date', [$monthStart, $monthEnd])
                ->sum('days');
            $leaveBalance = $leavePerMonth - $usedThisMonth; // cho phép âm

            $myKpis = Kpi::with('department')
                ->where('owner_employee_id', $employee->id)
                ->orWhereHas('phases', fn ($q) => $q->where('assignee_employee_id', $employee->id))
                ->latest()
                ->get();
            $myAvgKpi = (int) round($myKpis->avg('progress') ?? 0);

            // F13: Chấm công 7 ngày (giờ làm) của cá nhân bằng MỘT truy vấn gộp.
            $labels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
            $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = $startOfWeek->copy()->addDays(6);
            $minutesByDate = Attendance::where('employee_id', $employee->id)
                ->whereBetween('work_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
                ->selectRaw('DATE(work_date) as d, SUM(total_minutes) as m')
                ->groupBy('d')
                ->pluck('m', 'd');
            $maxHours = 1;
            for ($i = 0; $i < 7; $i++) {
                $date = $startOfWeek->copy()->addDays($i);
                $hours = round(((int) ($minutesByDate[$date->toDateString()] ?? 0)) / 60, 1);
                $maxHours = max($maxHours, $hours);
                $weekAttendance[] = [
                    'label' => $labels[$i],
                    'hours' => $hours,
                    'is_today' => $date->isSameDay($today),
                    'is_weekend' => $i >= 5,
                ];
            }
            foreach ($weekAttendance as &$day) {
                $day['pct'] = (int) round(($day['hours'] / $maxHours) * 100);
            }
            unset($day);

            $recentLeaves = LeaveRequest::where('employee_id', $employee->id)
                ->latest()
                ->take(4)
                ->get();
            $pendingCount = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->count();
        }

        return view('dashboard-personal', compact(
            'employee', 'todayRecord', 'workedDays', 'standardDays', 'lateThisMonth',
            'usedThisMonth', 'leaveBalance', 'leavePerMonth', 'myKpis', 'myAvgKpi',
            'weekAttendance', 'recentLeaves', 'pendingCount'
        ));
    }

    /**
     * F04: xác định hồ sơ nhân viên của người đăng nhập, ưu tiên quan hệ user_id,
     * dự phòng theo email — nhất quán với Attendance/Leave/Kpi controller.
     */
    private function currentEmployee(): ?Employee
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        return $user->employee ?? Employee::where('email', $user->email)->first();
    }
}
