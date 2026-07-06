<?php

namespace App\Console\Commands;

use App\Services\AttendanceCloser;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CloseAttendanceDay extends Command
{
    protected $signature = 'attendance:close-day {date? : Ngày cần chốt (YYYY-MM-DD). Bỏ trống để chốt tới hôm nay.}';

    protected $description = 'Đánh dấu vắng mặt cho nhân viên không chấm công trong ngày làm việc (bỏ qua cuối tuần & nghỉ phép đã duyệt).';

    public function handle(AttendanceCloser $closer): int
    {
        $date = $this->argument('date');

        if ($date) {
            try {
                $day = Carbon::parse($date)->startOfDay();
            } catch (\Throwable $e) {
                $this->error('Ngày không hợp lệ. Định dạng đúng: YYYY-MM-DD.');

                return self::FAILURE;
            }

            $count = $closer->closeDay($day);
            $this->info("Đã chốt ngày {$day->toDateString()}: tạo {$count} bản ghi vắng mặt.");

            return self::SUCCESS;
        }

        $count = $closer->run();
        $this->info("Đã chốt công tới hôm nay: tạo {$count} bản ghi vắng mặt.");

        return self::SUCCESS;
    }
}
