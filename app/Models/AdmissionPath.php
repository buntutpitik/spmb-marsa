<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionPath extends Model
{
    protected $fillable = [
        'period_id',
        'name',
        'code',
        'start_date',
        'end_date',
        'is_active',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PpdbPeriod::class, 'period_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'admission_path_id');
    }
}