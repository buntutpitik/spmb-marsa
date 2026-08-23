<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppProvider;
use App\Services\MetaWhatsappProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaWhatsappProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp.enabled', true);
        config()->set(
            'services.whatsapp.meta.access_token',
            'test-token'
        );
        config()->set(
            'services.whatsapp.meta.phone_number_id',
            '123456789'
        );
        config()->set(
            'services.whatsapp.meta.api_version',
            'v26.0'
        );
        config()->set(
            'services.whatsapp.meta.timeout',
            15
        );
    }

    public function test_meta_provider_implements_contract(): void
    {
        $provider = app(MetaWhatsappProvider::class);

        $this->assertInstanceOf(
            WhatsAppProvider::class,
            $provider
        );
    }

    public function test_meta_provider_sends_template_payload(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => '6281234567890',
                        'wa_id' => '6281234567890',
                    ],
                ],
                'messages' => [
                    [
                        'id' => 'wamid.test123',
                    ],
                ],
            ], 200),
        ]);

        $provider = app(MetaWhatsappProvider::class);

        $result = $provider->sendTemplate(
            '081234567890',
            'registration_success',
            'id',
            [
                'Ahmad Fauzan',
                'MARSA-2027-RPL-0001',
            ]
        );

        $this->assertTrue($result['success']);
        $this->assertSame(
            'wamid.test123',
            $result['message_id']
        );
        $this->assertFalse($result['retryable']);

        Http::assertSent(function ($request) {
            return $request->url()
                === 'https://graph.facebook.com/v26.0/123456789/messages'
                && $request['messaging_product'] === 'whatsapp'
                && $request['recipient_type'] === 'individual'
                && $request['to'] === '6281234567890'
                && $request['type'] === 'template'
                && $request['template']['name']
                    === 'registration_success'
                && $request['template']['language']['code']
                    === 'id'
                && $request['template']['components'][0]['parameters'][0]['text']
                    === 'Ahmad Fauzan'
                && $request['template']['components'][0]['parameters'][1]['text']
                    === 'MARSA-2027-RPL-0001';
        });
    }

    public function test_meta_provider_marks_server_error_as_retryable(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Temporary Meta error',
                ],
            ], 500),
        ]);

        $provider = app(MetaWhatsappProvider::class);

        $result = $provider->sendTemplate(
            '081234567890',
            'registration_success'
        );

        $this->assertFalse($result['success']);
        $this->assertTrue($result['retryable']);
        $this->assertSame(
            'Temporary Meta error',
            $result['error']
        );
        $this->assertSame(500, $result['status_code']);
    }

    public function test_meta_provider_marks_validation_error_as_permanent(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid parameter',
                ],
            ], 400),
        ]);

        $provider = app(MetaWhatsappProvider::class);

        $result = $provider->sendTemplate(
            '081234567890',
            'registration_success'
        );

        $this->assertFalse($result['success']);
        $this->assertFalse($result['retryable']);
        $this->assertSame(400, $result['status_code']);
    }
}