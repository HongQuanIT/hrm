<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class FinanceDebt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type', 'partner_name', 'partner_contact', 'amount',
        'due_date', 'status', 'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public const TYPE_LABELS = [
        'receivable' => 'Phải thu',
        'payable' => 'Phải trả',
    ];

    public const STATUS_LABELS = [
        'open' => 'Chưa thanh toán',
        'partially_paid' => 'Trả một phần',
        'paid' => 'Đã thanh toán',
        'overdue' => 'Quá hạn',
        'cancelled' => 'Đã huỷ',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'debt_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Số tiền đã thanh toán = Σ giao dịch gắn công nợ này.
     */
    public function getPaidAmountAttribute(): float
    {
        return (float) $this->transactions()->sum('amount');
    }

    /**
     * Số tiền còn lại = tổng − đã thanh toán (BR-M10-10).
     */
    public function getRemainingAmountAttribute(): float
    {
        return max((float) $this->amount - $this->paid_amount, 0);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && ! in_array($this->status, ['paid', 'cancelled'], true)
            && $this->due_date->lt(Carbon::today());
    }
}
