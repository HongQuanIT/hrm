<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'category_id', 'debt_id', 'direction', 'amount',
        'is_contribution', 'contributor_name', 'occurred_on',
        'description', 'reference', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_contribution' => 'boolean',
            'occurred_on' => 'date',
        ];
    }

    public const DIRECTION_LABELS = [
        'income' => 'Thu',
        'expense' => 'Chi',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id');
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(FinanceDebt::class, 'debt_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDirectionLabelAttribute(): string
    {
        if ($this->is_contribution) {
            return 'Nạp vốn';
        }

        return self::DIRECTION_LABELS[$this->direction] ?? $this->direction;
    }
}
