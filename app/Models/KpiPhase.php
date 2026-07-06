<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiPhase extends Model
{
    protected $fillable = [
        'kpi_id', 'name', 'assignee_employee_id', 'deadline', 'status',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
        ];
    }

    public const STATUS_LABELS = [
        'pending' => 'Chờ',
        'in_progress' => 'Đang làm',
        'done' => 'Đã xong',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_employee_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
