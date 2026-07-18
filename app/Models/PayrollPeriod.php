<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollPeriod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'month', 'year', 'days_in_month', 'status',
        'finance_transaction_id', 'note', 'created_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'days_in_month' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public const STATUS_LABELS = [
        'draft' => 'Nháp',
        'calculated' => 'Đã tính',
        'approved' => 'Đã duyệt',
        'paid' => 'Đã chi',
    ];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getLabelAttribute(): string
    {
        return $this->name ?: sprintf('Lương tháng %02d/%d', $this->month, $this->year);
    }

    /** Kỳ đã khoá (approved/paid) → không cho tính lại / sửa khoản tay. */
    public function getIsLockedAttribute(): bool
    {
        return in_array($this->status, ['approved', 'paid'], true);
    }

    public function getNetTotalAttribute(): float
    {
        return (float) $this->payslips->sum('net_amount');
    }
}
