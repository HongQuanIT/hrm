<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseChecklistItem extends Model
{
    protected $fillable = [
        'kpi_phase_id', 'title', 'is_done', 'position',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(KpiPhase::class, 'kpi_phase_id');
    }
}
