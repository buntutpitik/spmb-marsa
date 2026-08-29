<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicPageSetting extends Model
{
    protected $fillable = [
        'school_id',

        'hero_title',
        'hero_subtitle',
        'hero_description',

        'announcement_title',
        'announcement_body',
        'show_announcement',

        'requirements',
        'show_requirements',

        'registration_steps',
        'show_registration_steps',

        'reenrollment_information',
        'show_reenrollment_information',

        'show_contact',
    ];

    protected $casts = [
        'show_announcement' => 'boolean',
        'show_requirements' => 'boolean',
        'show_registration_steps' => 'boolean',
        'show_reenrollment_information' => 'boolean',
        'show_contact' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}