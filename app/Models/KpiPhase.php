<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class KpiPhase extends Model
{
    // F08: dùng soft-delete để không mất lịch sử/mốc thời gian khi giai đoạn bị gỡ khỏi form.
    use SoftDeletes;

    protected $fillable = [
        'kpi_id', 'name', 'description', 'priority', 'assignee_employee_id',
        'start_date', 'deadline', 'status',
        'received_at', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
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

    public const PRIORITY_LABELS = [
        'low' => 'Thấp',
        'medium' => 'Trung bình',
        'high' => 'Cao',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_employee_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(PhaseChecklistItem::class)->orderBy('position')->orderBy('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PhaseComment::class)->oldest();
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? ($this->priority ?? '—');
    }

    /**
     * Số mục checklist đã xong / tổng, phục vụ hiển thị tiến độ trên thẻ Kanban.
     */
    public function getChecklistDoneCountAttribute(): int
    {
        return $this->checklistItems->where('is_done', true)->count();
    }

    public function getChecklistTotalAttribute(): int
    {
        return $this->checklistItems->count();
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
