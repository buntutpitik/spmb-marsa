<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpdbWave extends Model
{
    protected $fillable = [
        'period_id',
        'name',
        'code',
        'start_date',
        'end_date',
        'registration_fee',
        'reenroll_fee',
        'quota',
        'is_active',
        'is_legacy',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_fee' => 'integer',
        'reenroll_fee' => 'integer',
        'quota' => 'integer',
        'is_active' => 'boolean',
        'is_legacy' => 'boolean',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PpdbPeriod::class, 'period_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'wave_id');
    }
}