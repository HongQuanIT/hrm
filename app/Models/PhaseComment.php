<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseComment extends Model
{
    protected $fillable = [
        'kpi_phase_id', 'user_id', 'body',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(KpiPhase::class, 'kpi_phase_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
