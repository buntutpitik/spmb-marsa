<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class School extends Model
{
    protected $fillable = [
        'name',
        'npsn',
        'address',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'phone',
        'whatsapp',
        'email',
        'website',
        'logo_path',
        'favicon_path',
    ];

    public function periods(): HasMany
    {
        return $this->hasMany(PpdbPeriod::class);
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }

    public function publicPageSetting(): HasOne
    {
        return $this->hasOne(PublicPageSetting::class);
    }
}