<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsappTemplateJob;
use App\Models\WhatsappLog;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendWhatsappTemplateJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_template_using_configured_fake_provider(): void
    {
        config()->set('whatsapp.provider', 'fake');
        config()->set('services.whatsapp.enabled', true);

        $log = WhatsappLog::create([
            'phone' => '081234567890',
            'message_type' => 'REGISTRATION_SUCCESS',
            'message' => 'Notifikasi pendaftaran berhasil.',
            'status' => 'PENDING',
            'attempt_count' => 0,
        ]);

        $job = new SendWhatsappTemplateJob(
            $log->id,
            'registration_success',
            'id',
            [
                'Ahmad Fauzan',
                'MARSA-2027-RPL-0001',
            ]
        );

        $job->handle(
            app(WhatsappService::class)
        );

        $log->refresh();

        $this->assertSame('SUCCESS', $log->status);
        $this->assertSame(
            'fake-message-id',
            $log->provider_message_id
        );
        $this->assertSame(1, $log->attempt_count);
        $this->assertNotNull($log->sent_at);
        $this->assertNull($log->failed_at);
        $this->assertNull($log->error_message);
    }

    public function test_job_does_nothing_when_whatsapp_is_disabled(): void
    {
        config()->set('whatsapp.provider', 'fake');
        config()->set('services.whatsapp.enabled', false);

        $log = WhatsappLog::create([
            'phone' => '081234567890',
            'message_type' => 'REGISTRATION_SUCCESS',
            'message' => 'Notifikasi pendaftaran berhasil.',
            'status' => 'PENDING',
            'attempt_count' => 0,
        ]);

        $job = new SendWhatsappTemplateJob(
            $log->id,
            'registration_success',
            'id',
            []
        );

        $job->handle(
            app(WhatsappService::class)
        );

        $log->refresh();

        $this->assertSame('PENDING', $log->status);
        $this->assertSame(0, $log->attempt_count);
        $this->assertNull($log->sent_at);
        $this->assertNull($log->failed_at);
        $this->assertNull($log->provider_message_id);
    }

    public function test_job_has_expected_retry_policy(): void
    {
        $job = new SendWhatsappTemplateJob(
            1,
            'registration_success',
            'id',
            []
        );

        $this->assertSame(3, $job->tries);
        $this->assertSame(
            [60, 300, 900],
            $job->backoff
        );
    }

    public function test_failed_marks_log_as_terminal_failed(): void
    {
        $log = WhatsappLog::create([
            'phone' => '081234567890',
            'message_type' => 'REGISTRATION_SUCCESS',
            'message' => 'Notifikasi pendaftaran berhasil.',
            'status' => 'PENDING',
            'attempt_count' => 3,
        ]);

        $job = new SendWhatsappTemplateJob(
            $log->id,
            'registration_success',
            'id',
            []
        );

        $job->failed(
            new \RuntimeException(
                'Queue exhausted.'
            )
        );

        $log->refresh();

        $this->assertSame(
            'FAILED',
            $log->status
        );

        $this->assertSame(
            'Queue exhausted.',
            $log->error_message
        );

        $this->assertNotNull(
            $log->failed_at
        );
    }
}