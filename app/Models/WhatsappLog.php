<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLog extends Model
{
    protected $attributes = [
        'provider' => 'META_CLOUD',
        'status' => 'PENDING',
        'attempt_count' => 0,
    ];

    protected $fillable = [
        'registration_id',
        'reenrollment_payment_id',
        'phone',
        'message_type',
        'message',
        'provider',
        'status',
        'provider_message_id',
        'response',
        'error_message',
        'attempt_count',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'attempt_count' => 'integer',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ReenrollmentPayment::class, 'reenrollment_payment_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}