<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class MetaWhatsappProvider
{
    public function __construct(
        protected WhatsappPhoneNormalizer $phoneNormalizer
    ) {
    }

    public function sendTemplate(
        string $phone,
        string $templateName,
        string $languageCode = 'id',
        array $bodyParameters = []
    ): array {
        if (! config('services.whatsapp.enabled')) {
            throw new RuntimeException(
                'Pengiriman WhatsApp dinonaktifkan.'
            );
        }

        $token = config('services.whatsapp.meta.access_token');
        $phoneNumberId = config('services.whatsapp.meta.phone_number_id');
        $apiVersion = config('services.whatsapp.meta.api_version');
        $timeout = (int) config(
            'services.whatsapp.meta.timeout',
            15
        );

        if (! $token) {
            throw new InvalidArgumentException(
                'META_WHATSAPP_ACCESS_TOKEN belum dikonfigurasi.'
            );
        }

        if (! $phoneNumberId) {
            throw new InvalidArgumentException(
                'META_WHATSAPP_PHONE_NUMBER_ID belum dikonfigurasi.'
            );
        }

        if (! $apiVersion) {
            throw new InvalidArgumentException(
                'META_WHATSAPP_API_VERSION belum dikonfigurasi.'
            );
        }

        $phone = $this->phoneNormalizer->normalize($phone);

        $template = [
            'name' => $templateName,
            'language' => [
                'code' => $languageCode,
            ],
        ];

        if ($bodyParameters !== []) {
            $template['components'] = [
                [
                    'type' => 'body',
                    'parameters' => array_map(
                        fn ($value) => [
                            'type' => 'text',
                            'text' => (string) $value,
                        ],
                        $bodyParameters
                    ),
                ],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'template',
            'template' => $template,
        ];

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $apiVersion,
            $phoneNumberId
        );

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout($timeout)
            ->post($url, $payload);

        return $this->parseResponse($response);
    }

    protected function parseResponse(Response $response): array
    {
        $json = $response->json();

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => data_get(
                    $json,
                    'messages.0.id'
                ),
                'response' => $json,
                'status_code' => $response->status(),
                'retryable' => false,
            ];
        }

        $statusCode = $response->status();

        $retryable = in_array(
            $statusCode,
            [
                429,
                500,
                502,
                503,
                504,
            ],
            true
        );

        return [
            'success' => false,
            'message_id' => null,

            'error' => data_get(
                $json,
                'error.message',
                'Meta WhatsApp API request failed.'
            ),

            'response' => $json,
            'status_code' => $statusCode,
            'retryable' => $retryable,
        ];
    }
}