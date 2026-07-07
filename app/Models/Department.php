<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['name', 'code', 'head_employee_id', 'head_name', 'color'];

    public function head(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'head_employee_id');
    }

    public function getHeadDisplayAttribute(): ?string
    {
        return $this->head?->name ?? $this->head_name;
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(Kpi::class);
    }
}
