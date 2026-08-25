<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Registration extends Model
{
    protected $fillable = [
        'period_id',
        'admission_path_id',
        'major_id',
        'registration_number',
        'nik',
        'nisn',
        'full_name',
        'birth_place',
        'birth_date',
        'gender',
        'religion',
        'origin_school',
        'hamlet',
        'rt',
        'rw',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'father_name',
        'mother_name',
        'father_job',
        'mother_job',
        'whatsapp',
        'graduation_score',
        'achievement_relief',
        'referrer_name',
        'referrer_source',
        'data_source',
        'status',
        'created_by',
        'registered_at',
        'accepted_at',
        'rejected_at',
        'reenrolled_at',
        'withdrawn_at',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'graduation_score' => 'decimal:2',
        'registered_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'reenrolled_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PpdbPeriod::class);
    }

    public function admissionPath(): BelongsTo
    {
        return $this->belongsTo(AdmissionPath::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(RegistrationStatusHistory::class);
    }

    public function reenrollmentPayments(): HasMany
    {
        return $this->hasMany(ReenrollmentPayment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function whatsappLogs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class);
    }
    public function reliefOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            ReliefOption::class,
            'registration_relief_options'
        )->withTimestamps();
    }

    public function specialPrograms(): BelongsToMany
    {
        return $this->belongsToMany(
            SpecialProgram::class,
            'registration_special_programs'
        )->withTimestamps();
    }
}