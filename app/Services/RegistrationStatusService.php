<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Registration;
use App\Models\RegistrationStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrationStatusService
{
    private const ALLOWED_TRANSITIONS = [
        'REGISTERED' => [
            'ACCEPTED',
            'REJECTED',
            'WITHDRAWN',
        ],

        'ACCEPTED' => [
            'REENROLLED',
            'WITHDRAWN',
        ],

        'REJECTED' => [],

        'REENROLLED' => [
            'WITHDRAWN',
        ],

        'WITHDRAWN' => [],
    ];

    public function __construct(
        protected WhatsappNotificationService $notificationService
    ) {
    }

    public function change(
        Registration $registration,
        string $newStatus,
        ?User $user = null,
        ?string $notes = null
    ): Registration {
        $newStatus = strtoupper(trim($newStatus));

        return DB::transaction(function () use (
            $registration,
            $newStatus,
            $user,
            $notes
        ) {
            /*
             * Ambil ulang sekaligus lock registration.
             * Mencegah dua perubahan status bersamaan.
             */
            $registration = Registration::query()
                ->lockForUpdate()
                ->findOrFail($registration->id);

            $oldStatus = $registration->status;

            if ($oldStatus === $newStatus) {
                throw new InvalidArgumentException(
                    "Status pendaftaran sudah {$newStatus}."
                );
            }

            $allowed = self::ALLOWED_TRANSITIONS[$oldStatus] ?? [];

            if (! in_array($newStatus, $allowed, true)) {
                throw new InvalidArgumentException(
                    "Perubahan status {$oldStatus} ke {$newStatus} tidak diizinkan."
                );
            }

            /*
             * Timestamp status menjadi jejak bahwa status tersebut
             * pernah dicapai.
             */
            $updates = [
                'status' => $newStatus,
            ];

            switch ($newStatus) {
                case 'ACCEPTED':
                    $updates['accepted_at'] = now();
                    break;

                case 'REJECTED':
                    $updates['rejected_at'] = now();
                    break;

                case 'REENROLLED':
                    $updates['reenrolled_at'] = now();
                    break;

                case 'WITHDRAWN':
                    $updates['withdrawn_at'] = now();
                    break;
            }

            $registration->update($updates);

            /*
             * Simpan history perubahan status.
             */
            RegistrationStatusHistory::create([
                'registration_id' => $registration->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'changed_by' => $user?->id,
                'changed_at' => now(),
                'notes' => $notes,
            ]);

            /*
             * Activity log global.
             */
            ActivityLog::create([
                'user_id' => $user?->id,
                'registration_id' => $registration->id,
                'action' => 'CHANGE_STATUS',
                'description' => "Status pendaftaran diubah dari {$oldStatus} menjadi {$newStatus}.",
                'metadata' => [
                    'registration_number' => $registration->registration_number,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                ],
            ]);

            /*
             * Queue notifikasi WhatsApp sesuai status.
             *
             * REENROLLED tidak dikirim dari service ini karena
             * notifikasi daftar ulang dikendalikan oleh
             * ReenrollmentPaymentService setelah pelunasan.
             */
            match ($newStatus) {
                'ACCEPTED' => $this->notificationService
                    ->registrationAccepted($registration),

                'REJECTED' => $this->notificationService
                    ->registrationRejected($registration),

                'WITHDRAWN' => $this->notificationService
                    ->registrationWithdrawn($registration),

                default => null,
            };

            return $registration->refresh()->load([
                'statusHistories',
                'activityLogs',
                'whatsappLogs',
            ]);
        });
    }
}