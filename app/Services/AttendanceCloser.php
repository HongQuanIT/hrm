<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;

/**
 * Chốt công cuối ngày: nhân viên không chấm công (không bấm gì) trong ngày làm việc
 * và không nghỉ phép đã duyệt sẽ được đánh dấu là Vắng mặt.
 *
 * Chạy tự động (self-gated qua mốc attendance_closed_through, tối đa 1 lần/ngày)
 * hoặc thủ công qua lệnh `php artisan attendance:close-day`.
 */
class AttendanceCloser
{
    // Giới hạn số ngày truy hồi ở lần chạy đầu để tránh xử lý toàn bộ lịch sử.
    private const MAX_LOOKBACK_DAYS = 31;

    /**
     * Chốt các ngày còn thiếu tính đến hôm nay (chỉ chốt hôm nay khi đã qua hạn check-in).
     * Trả về tổng số bản ghi vắng mặt được tạo.
     */
    public function run(): int
    {
        $today = Carbon::today();

        // Chốt các ngày đã qua mà nhân viên có check-in nhưng quên check-out.
        $this->resolveMissingCheckouts($today);

        $deadline = $this->timeOn($today, 'checkin_deadline', '10:00');

        // Hôm nay chỉ được chốt khi đã qua hạn check-in; nếu chưa thì chốt tới hôm qua.
        $through = Carbon::now()->greaterThan($deadline) ? $today : $today->copy()->subDay();

        $closedThrough = CompanySetting::get('attendance_closed_through');
        $start = $closedThrough
            ? Carbon::parse($closedThrough)->addDay()->startOfDay()
            : $today->copy()->subDays(self::MAX_LOOKBACK_DAYS);

        // Không lùi quá xa kể cả khi mốc bị thiếu.
        $earliest = $today->copy()->subDays(self::MAX_LOOKBACK_DAYS);
        if ($start->lessThan($earliest)) {
            $start = $earliest;
        }

        if ($start->greaterThan($through)) {
            return 0;
        }

        $created = 0;
        for ($date = $start->copy(); $date->lessThanOrEqualTo($through); $date->addDay()) {
            $created += $this->closeDay($date);
        }

        CompanySetting::put('attendance_closed_through', $through->toDateString());

        return $created;
    }

    /**
     * Đánh dấu vắng mặt cho một ngày cụ thể. Bỏ qua cuối tuần, người đã có bản ghi
     * chấm công và người đang nghỉ phép đã duyệt. Trả về số bản ghi được tạo.
     */
    public function closeDay(Carbon $date): int
    {
        if ($date->isWeekend()) {
            return 0;
        }

        $employeeIds = Employee::where('status', '!=', 'resigned')->pluck('id');
        if ($employeeIds->isEmpty()) {
            return 0;
        }

        $haveRecord = Attendance::whereDate('work_date', $date)
            ->pluck('employee_id')
            ->all();

        $onApprovedLeave = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->pluck('employee_id')
            ->all();

        $skip = array_flip(array_merge($haveRecord, $onApprovedLeave));

        $now = now();
        $rows = [];
        foreach ($employeeIds as $id) {
            if (isset($skip[$id])) {
                continue;
            }
            $rows[] = [
                'employee_id' => $id,
                'work_date' => $date->toDateString(),
                'check_in' => null,
                'check_out' => null,
                'total_minutes' => 0,
                'late_minutes' => 0,
                'status' => 'absent',
                'note' => 'Không chấm công trong ngày làm việc.',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            Attendance::insert($rows);
        }

        return count($rows);
    }

    /**
     * Với những ngày đã kết thúc (trước hôm nay), nếu nhân viên đã check-in nhưng
     * quên check-out thì đánh dấu "Quên check-out" và không tính công (total = 0).
     * Ngày hôm nay không xử lý vì vẫn đang trong ca làm việc ("Đang làm việc").
     */
    public function resolveMissingCheckouts(Carbon $today): int
    {
        return Attendance::whereDate('work_date', '<', $today->toDateString())
            ->whereDate('work_date', '>=', $today->copy()->subDays(self::MAX_LOOKBACK_DAYS)->toDateString())
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->whereIn('status', ['working', 'late', 'on_time'])
            ->update([
                'status' => 'missing_checkout',
                'total_minutes' => 0,
                'note' => 'Quên check-out — không được tính công.',
                'updated_at' => now(),
            ]);
    }

    private function timeOn(Carbon $date, string $key, string $default): Carbon
    {
        $value = (string) CompanySetting::get($key, $default);
        [$hour, $minute] = array_pad(explode(':', $value), 2, '0');

        return $date->copy()->setTime((int) $hour, (int) $minute, 0);
    }
}
