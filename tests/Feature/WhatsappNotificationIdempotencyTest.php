<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsappTemplateJob;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsappNotificationIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_registration_event_is_queued_only_once(): void
    {
        Queue::fake();

        config()->set(
            'whatsapp.templates.registration_success',
            [
                'name' => 'registration_success',
                'language' => 'id',
            ]
        );

        $school = School::create([
            'name' => 'SMK WHATSAPP IDEMPOTENCY TEST',
        ]);

        $period = PpdbPeriod::create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_year' => 2027,
        ]);

        $path = AdmissionPath::create([
            'period_id' => $period->id,
            'name' => 'UMUM',
            'code' => 'UMUM-IDEMPOTENCY',
            'is_active' => true,
        ]);

        $major = Major::create([
            'school_id' => $school->id,
            'code' => 'WAI',
            'name' => 'JURUSAN WA IDEMPOTENCY',
            'is_active' => true,
        ]);

        $registration = Registration::create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,
            'registration_number' => 'WA-IDEMPOTENCY-001',
            'nik' => '3311111111000001',
            'full_name' => 'Ahmad Fauzan',
            'origin_school' => 'SMP TEST',
            'whatsapp' => '081211110001',
            'data_source' => 'ADMIN',
            'status' => 'REGISTERED',
            'registered_at' => now(),
        ]);

        $service = app(
            WhatsappNotificationService::class
        );

        $firstLog = $service->registrationSuccess(
            $registration
        );

        $secondLog = $service->registrationSuccess(
            $registration
        );

        $this->assertSame(
            $firstLog->id,
            $secondLog->id
        );

        $this->assertDatabaseCount(
            'whatsapp_logs',
            1
        );

        $this->assertDatabaseHas(
            'whatsapp_logs',
            [
                'registration_id' => $registration->id,
                'message_type' => 'REGISTRATION_SUCCESS',
            ]
        );

        Queue::assertPushed(
            SendWhatsappTemplateJob::class,
            1
        );
    }
}