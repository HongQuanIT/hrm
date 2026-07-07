<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 'work_date', 'check_in', 'check_out',
        'total_minutes', 'late_minutes', 'status', 'note',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'total_minutes' => 'integer',
            'late_minutes' => 'integer',
        ];
    }

    public const STATUS_LABELS = [
        'on_time' => 'Đúng giờ',
        'late' => 'Đi muộn',
        'absent' => 'Vắng mặt',
        'leave' => 'Nghỉ phép',
        'working' => 'Đang làm việc',
        'missing_checkout' => 'Quên check-out',
    ];

    // Nhãn cho từng mức cảnh báo đi muộn.
    public const LATE_LEVEL_LABELS = [
        1 => 'Muộn nhẹ',
        2 => 'Muộn',
        3 => 'Muộn nghiêm trọng',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getTotalHoursAttribute(): string
    {
        $h = intdiv($this->total_minutes, 60);
        $m = $this->total_minutes % 60;

        return sprintf('%dh %02dm', $h, $m);
    }

    /**
     * Mức cảnh báo đi muộn: 0 = không muộn, 1 = nhẹ, 2 = vừa, 3 = nghiêm trọng.
     * Ngưỡng lấy từ cấu hình công ty.
     */
    public function getLateLevelAttribute(): int
    {
        if ($this->status !== 'late' || $this->late_minutes <= 0) {
            return 0;
        }

        $level1 = (int) CompanySetting::get('late_level1_minutes', 15);
        $level2 = (int) CompanySetting::get('late_level2_minutes', 30);

        if ($this->late_minutes <= $level1) {
            return 1;
        }

        if ($this->late_minutes <= $level2) {
            return 2;
        }

        return 3;
    }

    public function getLateLevelLabelAttribute(): string
    {
        return self::LATE_LEVEL_LABELS[$this->late_level] ?? 'Đi muộn';
    }
}
