<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Chốt công hằng ngày: nhân viên không chấm công sẽ bị đánh dấu vắng mặt.
// Cần cron chạy `php artisan schedule:run` mỗi phút để lịch này hoạt động.
Schedule::command('attendance:close-day')->dailyAt('23:30');
