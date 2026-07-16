<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCategory extends Model
{
    protected $fillable = ['name', 'direction', 'color'];

    public const DIRECTION_LABELS = [
        'income' => 'Thu',
        'expense' => 'Chi',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'category_id');
    }

    public function getDirectionLabelAttribute(): string
    {
        return self::DIRECTION_LABELS[$this->direction] ?? $this->direction;
    }
}
