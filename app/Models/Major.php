<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Major extends Model
{
    protected $fillable = [
        'school_id',
        'code',
        'name',
        'short_name',
        'description',
        'icon_path',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function periodMajors(): HasMany
    {
        return $this->hasMany(PeriodMajor::class);
    }

    public function periods(): BelongsToMany
    {
        return $this->belongsToMany(
            PpdbPeriod::class,
            'period_majors',
            'major_id',
            'period_id'
        )
            ->withPivot(['id', 'quota', 'is_active'])
            ->withTimestamps();
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function registrationSequences(): HasMany
    {
        return $this->hasMany(RegistrationSequence::class);
    }
}