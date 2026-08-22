<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SpecialProgram extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function periods(): BelongsToMany
    {
        return $this->belongsToMany(
            PpdbPeriod::class,
            'period_special_programs'
        )
            ->withPivot([
                'is_active',
                'sort_order',
            ])
            ->withTimestamps();
    }

    public function registrations(): BelongsToMany
    {
        return $this->belongsToMany(
            Registration::class,
            'registration_special_programs'
        )->withTimestamps();
    }
}