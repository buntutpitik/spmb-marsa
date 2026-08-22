<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpdbPeriod extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'year_start',
        'year_end',
        'registration_open',
        'registration_close',
        'status',
        'is_active',
        'principal_name',
        'principal_nip',
        'number_prefix',
        'number_year',
        'number_digits',
        'include_major_code',
        'default_reenroll_fee',
        'notes',
        'archived_at',
    ];

    protected $casts = [
        'year_start' => 'integer',
        'year_end' => 'integer',
        'registration_open' => 'date',
        'registration_close' => 'date',
        'is_active' => 'boolean',
        'number_year' => 'integer',
        'number_digits' => 'integer',
        'include_major_code' => 'boolean',
        'default_reenroll_fee' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function waves(): HasMany
    {
        return $this->hasMany(PpdbWave::class, 'period_id');
    }

    public function admissionPaths(): HasMany
    {
        return $this->hasMany(AdmissionPath::class, 'period_id');
    }

    public function periodMajors(): HasMany
    {
        return $this->hasMany(PeriodMajor::class, 'period_id');
    }

    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(
            Major::class,
            'period_majors',
            'period_id',
            'major_id'
        )
            ->withPivot([
                'id',
                'quota',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'period_id');
    }

    public function registrationSequences(): HasMany
    {
        return $this->hasMany(RegistrationSequence::class, 'period_id');
    }

    public function reliefOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            ReliefOption::class,
            'period_relief_options'
        )
            ->withPivot([
                'is_active',
                'sort_order',
            ])
            ->withTimestamps();
    }

    public function specialPrograms(): BelongsToMany
    {
        return $this->belongsToMany(
            SpecialProgram::class,
            'period_special_programs'
        )
            ->withPivot([
                'is_active',
                'sort_order',
            ])
            ->withTimestamps();
    }
}