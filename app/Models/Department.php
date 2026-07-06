<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['name', 'code', 'head_name', 'color'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(Kpi::class);
    }
}
