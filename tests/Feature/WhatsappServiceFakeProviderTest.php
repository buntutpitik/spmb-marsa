<?php

namespace Tests\Feature;

use App\Models\WhatsappLog;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappServiceFakeProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_service_sends_using_fake_provider(): void
    {
        config()->set('whatsapp.provider', 'fake');

        $log = WhatsappLog::create([
            'phone' => '081234567890',
            'message_type' => 'REGISTRATION_SUCCESS',
            'message' => 'Notifikasi pendaftaran berhasil.',
        ]);

        $service = app(WhatsappService::class);

        $result = $service->sendTemplate(
            $log,
            'registration_success',
            'id',
            [
                'Ahmad Fauzan',
                'MARSA-2027-RPL-0001',
            ]
        );

        $updatedLog = $result['log'];

        $this->assertSame('SUCCESS', $updatedLog->status);
        $this->assertSame(
            'fake-message-id',
            $updatedLog->provider_message_id
        );
        $this->assertSame(1, $updatedLog->attempt_count);
        $this->assertNotNull($updatedLog->sent_at);
        $this->assertNull($updatedLog->failed_at);
        $this->assertFalse($result['retryable']);
    }
}