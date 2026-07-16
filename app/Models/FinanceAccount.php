<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'type', 'bank_name', 'account_number',
        'opening_balance', 'currency', 'is_active', 'note',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public const TYPE_LABELS = [
        'cash' => 'Tiền mặt',
        'bank' => 'Ngân hàng',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'account_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    /**
     * Số dư hiện tại = số dư đầu kỳ + Σ thu − Σ chi (tính từ sổ giao dịch chưa xoá).
     * Có thể âm (BR-M10-03).
     */
    public function getBalanceAttribute(): float
    {
        $income = (float) $this->transactions()->where('direction', 'income')->sum('amount');
        $expense = (float) $this->transactions()->where('direction', 'expense')->sum('amount');

        return (float) $this->opening_balance + $income - $expense;
    }
}
