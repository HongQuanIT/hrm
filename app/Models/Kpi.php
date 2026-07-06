<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kpi extends Model
{
    protected $fillable = [
        'name', 'description', 'department_id', 'owner_employee_id',
        'measure_type', 'unit', 'target_value', 'current_value',
        'progress', 'priority', 'status', 'deadline',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
        ];
    }

    public const MEASURE_LABELS = [
        'percent' => 'Phần trăm (%)',
        'count' => 'Số lượng (Count)',
        'milestone' => 'Milestone (Cột mốc)',
    ];

    public const PRIORITY_LABELS = [
        'low' => 'Thấp',
        'medium' => 'Trung bình',
        'high' => 'Cao',
    ];

    public const STATUS_LABELS = [
        'on_track' => 'Đúng hạn',
        'in_progress' => 'Đang xử lý',
        'behind' => 'Chậm tiến độ',
        'done' => 'Hoàn thành',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(KpiPhase::class);
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? $this->priority;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
