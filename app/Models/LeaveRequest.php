<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id', 'type', 'start_date', 'end_date', 'days',
        'reason', 'status', 'approver_name', 'attachment',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'decimal:1',
        ];
    }

    public const TYPE_LABELS = [
        'monthly' => 'Nghỉ phép tháng',
        'annual' => 'Nghỉ phép năm',
        'sick' => 'Nghỉ ốm',
        'unpaid' => 'Nghỉ không lương',
        'maternity' => 'Chế độ thai sản/kết hôn',
        'remote' => 'Làm việc từ xa',
    ];

    public const STATUS_LABELS = [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
        'cancelled' => 'Đã hủy',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
