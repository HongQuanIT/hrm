<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class KpiPhase extends Model
{
    protected $fillable = [
        'kpi_id', 'name', 'assignee_employee_id', 'deadline', 'status',
        'received_at', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'received_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Chờ nhận',
        self::STATUS_RECEIVED => 'Đã nhận',
        self::STATUS_IN_PROGRESS => 'Đang làm',
        self::STATUS_DONE => 'Đã xong',
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

    /**
     * Giai đoạn bị trễ khi đã quá hạn mà chưa hoàn thành.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline
            && $this->status !== self::STATUS_DONE
            && $this->deadline->lt(Carbon::today());
    }

    /**
     * Hoàn thành nhưng trễ hạn (để hiển thị "Hoàn thành trễ").
     */
    public function getCompletedLateAttribute(): bool
    {
        return $this->status === self::STATUS_DONE
            && $this->deadline
            && $this->completed_at
            && $this->completed_at->gt($this->deadline->endOfDay());
    }
}
