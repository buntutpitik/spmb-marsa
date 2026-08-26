<?php

namespace App\Services;

use App\Jobs\SendWhatsappTemplateJob;
use App\Models\Registration;
use App\Models\WhatsappLog;
use InvalidArgumentException;

class WhatsappNotificationService
{
    public function queue(
        Registration $registration,
        string $event,
        array $bodyParameters,
        ?string $message = null
    ): WhatsappLog {
        $template = config("whatsapp.templates.{$event}");

        if (! is_array($template)) {
            throw new InvalidArgumentException(
                "Template WhatsApp untuk event {$event} tidak ditemukan."
            );
        }

        $templateName = $template['name'] ?? null;
        $languageCode = $template['language'] ?? 'id';

        if (! $templateName) {
            throw new InvalidArgumentException(
                "Nama template WhatsApp untuk event {$event} belum dikonfigurasi."
            );
        }

        $messageType = strtoupper($event);

        $log = WhatsappLog::query()->firstOrCreate(
            [
                'registration_id' => $registration->id,
                'message_type' => $messageType,
            ],
            [
                'phone' => $registration->whatsapp,
                'message' => $message ?? $event,
                'status' => 'PENDING',
                'attempt_count' => 0,
            ]
        );

        if ($log->wasRecentlyCreated) {
            SendWhatsappTemplateJob::dispatch(
                $log->id,
                $templateName,
                $languageCode,
                $bodyParameters
            )->afterCommit();
        }

        return $log;
    }

    public function registrationSuccess(
        Registration $registration
    ): WhatsappLog {
        return $this->queue(
            $registration,
            'registration_success',
            [
                $registration->full_name,
                $registration->registration_number,
            ],
            'Notifikasi pendaftaran berhasil.'
        );
    }

    public function registrationAccepted(
        Registration $registration
    ): WhatsappLog {
        return $this->queue(
            $registration,
            'registration_accepted',
            [
                $registration->full_name,
                $registration->registration_number,
            ],
            'Notifikasi pendaftaran diterima.'
        );
    }

    public function registrationRejected(
        Registration $registration
    ): WhatsappLog {
        return $this->queue(
            $registration,
            'registration_rejected',
            [
                $registration->full_name,
                $registration->registration_number,
            ],
            'Notifikasi pendaftaran ditolak.'
        );
    }

    public function reenrollmentComplete(
        Registration $registration
    ): WhatsappLog {
        return $this->queue(
            $registration,
            'reenrollment_complete',
            [
                $registration->full_name,
                $registration->registration_number,
            ],
            'Notifikasi daftar ulang selesai.'
        );
    }

    public function registrationWithdrawn(
        Registration $registration
    ): WhatsappLog {
        return $this->queue(
            $registration,
            'registration_withdrawn',
            [
                $registration->full_name,
                $registration->registration_number,
            ],
            'Notifikasi pengunduran diri.'
        );
    }
}