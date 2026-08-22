<?php

namespace App\Services;

use App\Models\WhatsappLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class WhatsappService
{
    public function __construct(
        protected MetaWhatsappProvider $provider
    ) {
    }

    public function markSuccess(
        WhatsappLog $log,
        ?string $providerMessageId = null,
        mixed $response = null
    ): WhatsappLog {
        return DB::transaction(function () use (
            $log,
            $providerMessageId,
            $response
        ) {
            $log = WhatsappLog::query()
                ->lockForUpdate()
                ->findOrFail($log->id);

            $log->update([
                'status' => 'SUCCESS',
                'provider_message_id' => $providerMessageId,
                'response' => $this->normalizeResponse($response),
                'error_message' => null,
                'sent_at' => now(),
                'failed_at' => null,
            ]);

            return $log->refresh();
        });
    }

    public function markFailed(
        WhatsappLog $log,
        string $errorMessage,
        mixed $response = null
    ): WhatsappLog {
        return DB::transaction(function () use (
            $log,
            $errorMessage,
            $response
        ) {
            $log = WhatsappLog::query()
                ->lockForUpdate()
                ->findOrFail($log->id);

            $log->update([
                'status' => 'FAILED',
                'response' => $this->normalizeResponse($response),
                'error_message' => $errorMessage,
                'failed_at' => now(),
                'sent_at' => null,
            ]);

            return $log->refresh();
        });
    }

    public function beginAttempt(WhatsappLog $log): WhatsappLog
    {
        return DB::transaction(function () use ($log) {
            $log = WhatsappLog::query()
                ->lockForUpdate()
                ->findOrFail($log->id);

            if ($log->status === 'SUCCESS') {
                throw new InvalidArgumentException(
                    'Pesan WhatsApp yang sudah SUCCESS tidak boleh dikirim ulang.'
                );
            }

            $log->increment('attempt_count');

            $log->update([
                'status' => 'PENDING',
                'error_message' => null,
                'failed_at' => null,
            ]);

            return $log->refresh();
        });
    }

    public function canRetry(
        WhatsappLog $log,
        int $maxAttempts = 3
    ): bool {
        if ($maxAttempts <= 0) {
            return false;
        }

        return $log->status === 'FAILED'
            && $log->attempt_count < $maxAttempts;
    }

    public function sendTemplate(
        WhatsappLog $log,
        string $templateName,
        string $languageCode = 'id',
        array $bodyParameters = []
    ): array {
        $log = $this->beginAttempt($log);

        try {
            $result = $this->provider->sendTemplate(
                $log->phone,
                $templateName,
                $languageCode,
                $bodyParameters
            );

            if ($result['success'] ?? false) {
                $log = $this->markSuccess(
                    $log,
                    $result['message_id'] ?? null,
                    $result['response'] ?? null
                );

                return [
                    'log' => $log,
                    'retryable' => false,
                ];
            }

            $log = $this->markFailed(
                $log,
                $result['error'] ?? 'Pengiriman WhatsApp gagal.',
                $result['response'] ?? null
            );

            return [
                'log' => $log,
                'retryable' => (bool) ($result['retryable'] ?? false),
            ];
        } catch (Throwable $exception) {
            $log = $this->markFailed(
                $log,
                $exception->getMessage()
            );

            return [
                'log' => $log,
                'retryable' => true,
            ];
        }
    }

    private function normalizeResponse(mixed $response): ?string
    {
        if ($response === null) {
            return null;
        }

        if (is_string($response)) {
            return $response;
        }

        return json_encode(
            $response,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }
}