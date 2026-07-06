<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Kpi;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(\App\Services\AttendanceCloser $closer)
    {
        // Đồng bộ trạng thái vắng mặt trước khi hiển thị số liệu.
        $closer->run();

        if (auth()->user()->isSuperAdmin()) {
            return $this->adminDashboard();
        }

        return $this->personalDashboard();
    }

    private function adminDashboard()
    {
        $today = Carbon::today();

        $totalEmployees = Employee::count();
        $onLeaveToday = Employee::where('status', 'on_leave')->count();
        $lateToday = Attendance::whereDate('work_date', $today)->where('status', 'late')->count();
        $workingToday = max($totalEmployees - $onLeaveToday, 0);
        $avgKpi = (int) round(Kpi::avg('progress') ?? 0);

        // 7-day attendance (present count per day)
        $weekAttendance = [];
        $labels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
        $maxCount = 1;
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $count = Attendance::whereDate('work_date', $date)
                ->whereIn('status', ['on_time', 'late', 'working'])
                ->count();
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

        return view('dashboard', compact(
            'totalEmployees', 'workingToday', 'onLeaveToday', 'lateToday', 'avgKpi',
            'weekAttendance', 'leaveUsed', 'leaveQuota', 'leavePct',
            'newEmployees', 'pendingLeaves', 'recentLeaves'
        ));
    }

    private function personalDashboard()
    {
        $user = auth()->user();
        $employee = Employee::with('department')->where('email', $user->email)->first();

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $leavePerMonth = (int) CompanySetting::get('leave_days_per_month', 1);

        // Mặc định an toàn khi tài khoản chưa gắn hồ sơ nhân viên.
        $todayRecord = null;
        $workedDays = 0;
        $standardDays = max($today->daysInMonth - $leavePerMonth, 0);
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

            // Chấm công 7 ngày (giờ làm) của cá nhân
            $labels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
            $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
            $maxHours = 1;
            for ($i = 0; $i < 7; $i++) {
                $date = $startOfWeek->copy()->addDays($i);
                $rec = Attendance::where('employee_id', $employee->id)
                    ->whereDate('work_date', $date)
                    ->first();
                $hours = $rec ? round($rec->total_minutes / 60, 1) : 0;
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
}
