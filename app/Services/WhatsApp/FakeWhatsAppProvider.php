<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppProvider;

class FakeWhatsAppProvider implements WhatsAppProvider
{
    public function sendTemplate(
        string $recipient,
        string $template,
        string $languageCode = 'id',
        array $parameters = []
    ): array {
        return [
            'success' => true,
            'message_id' => 'fake-message-id',
            'response' => [
                'provider' => 'fake',
                'recipient' => $recipient,
                'template' => $template,
                'language' => $languageCode,
            ],
            'status_code' => 200,
            'retryable' => false,
        ];
    }
}