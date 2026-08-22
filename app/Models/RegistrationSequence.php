<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationSequence extends Model
{
    protected $fillable = [
        'period_id',
        'major_id',
        'sequence_key',
        'current_number',
    ];

    protected $casts = [
        'current_number' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PpdbPeriod::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }
}