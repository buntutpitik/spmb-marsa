<?php

namespace Tests\Unit;

use App\Contracts\WhatsAppProvider;
use App\Services\WhatsApp\FakeWhatsAppProvider;
use PHPUnit\Framework\TestCase;

class WhatsAppProviderTest extends TestCase
{
    public function test_fake_provider_implements_provider_contract(): void
    {
        $provider = new FakeWhatsAppProvider();

        $this->assertInstanceOf(
            WhatsAppProvider::class,
            $provider
        );
    }

    public function test_fake_provider_returns_successful_result(): void
    {
        $provider = new FakeWhatsAppProvider();

        $result = $provider->sendTemplate(
            '6281234567890',
            'registration_success',
            'id',
            [
                'Ahmad Fauzan',
                'MARSA-2027-RPL-0001',
            ]
        );

        $this->assertTrue($result['success']);
        $this->assertSame(
            'fake-message-id',
            $result['message_id']
        );
        $this->assertSame(200, $result['status_code']);
        $this->assertFalse($result['retryable']);

        $this->assertSame(
            '6281234567890',
            $result['response']['recipient']
        );

        $this->assertSame(
            'registration_success',
            $result['response']['template']
        );

        $this->assertSame(
            'id',
            $result['response']['language']
        );
    }
}