<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodMajor extends Model
{
    protected $fillable = [
        'period_id',
        'major_id',
        'quota',
        'is_active',
    ];

    protected $casts = [
        'quota' => 'integer',
        'is_active' => 'boolean',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PpdbPeriod::class, 'period_id');
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }
}