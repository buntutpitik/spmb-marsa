<?php

namespace App\Jobs;

use App\Models\WhatsappLog;
use App\Services\WhatsappService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class SendWhatsappTemplateJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [
        60,
        300,
        900,
    ];

    public function __construct(
        public int $whatsappLogId,
        public string $templateName,
        public string $languageCode = 'id',
        public array $bodyParameters = []
    ) {
    }

    public function handle(
        WhatsappService $whatsappService
    ): void {
        $log = WhatsappLog::query()
            ->find($this->whatsappLogId);

        if (! $log) {
            return;
        }

        if ($log->status === 'SUCCESS') {
            return;
        }

        if (! config('services.whatsapp.enabled', false)) {
            return;
        }

        if (
            $log->status === 'FAILED'
            && ! $whatsappService->canRetry($log, $this->tries)
        ) {
            return;
        }

        $result = $whatsappService->sendTemplate(
            $log,
            $this->templateName,
            $this->languageCode,
            $this->bodyParameters
        );

        /** @var WhatsappLog $updatedLog */
        $updatedLog = $result['log'];

        $retryable = (bool) ($result['retryable'] ?? false);

        if (
            $updatedLog->status === 'FAILED'
            && $retryable
        ) {
            throw new RuntimeException(
                $updatedLog->error_message
                    ?? 'Pengiriman WhatsApp gagal.'
            );
        }

        /*
         * FAILED tetapi permanent:
         * jangan throw exception, sehingga job dianggap selesai
         * dan tidak di-retry oleh queue.
         */
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $log = WhatsappLog::query()
            ->find($this->whatsappLogId);

        if (! $log || $log->status === 'SUCCESS') {
            return;
        }

        $log->update([
            'status' => 'FAILED',
            'error_message' => $exception?->getMessage()
                ?? $log->error_message
                ?? 'Job WhatsApp gagal permanen.',
            'failed_at' => now(),
        ]);
    }
}