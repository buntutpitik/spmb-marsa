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

class WhatsappNotificationServiceExistingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_success_creates_log_and_dispatches_job(): void
    {
        Queue::fake();

        config()->set('whatsapp.templates.registration_success', [
            'name' => 'registration_success',
            'language' => 'id',
        ]);

        $registration = $this->makeRegistration(
            status: 'REGISTERED',
            name: 'Ahmad Fauzan'
        );

        $log = app(WhatsappNotificationService::class)
            ->registrationSuccess($registration);

        $this->assertSame(
            $registration->id,
            $log->registration_id
        );

        $this->assertSame(
            $registration->whatsapp,
            $log->phone
        );

        $this->assertSame(
            'REGISTRATION_SUCCESS',
            $log->message_type
        );

        $this->assertSame(
            'PENDING',
            $log->status
        );

        $this->assertSame(
            0,
            $log->attempt_count
        );

        Queue::assertPushed(
            SendWhatsappTemplateJob::class,
            function ($job) use ($log, $registration) {
                return $job->whatsappLogId === $log->id
                    && $job->templateName === 'registration_success'
                    && $job->languageCode === 'id'
                    && $job->bodyParameters === [
                        $registration->full_name,
                        $registration->registration_number,
                    ];
            }
        );
    }

    public function test_registration_accepted_uses_accepted_template(): void
    {
        Queue::fake();

        config()->set('whatsapp.templates.registration_accepted', [
            'name' => 'registration_accepted',
            'language' => 'id',
        ]);

        $registration = $this->makeRegistration(
            status: 'ACCEPTED',
            name: 'Ahmad Fauzan'
        );

        $log = app(WhatsappNotificationService::class)
            ->registrationAccepted($registration);

        $this->assertSame(
            'REGISTRATION_ACCEPTED',
            $log->message_type
        );

        Queue::assertPushed(
            SendWhatsappTemplateJob::class,
            fn ($job) =>
                $job->templateName === 'registration_accepted'
        );
    }

    public function test_reenrollment_complete_uses_reenrollment_template(): void
    {
        Queue::fake();

        config()->set('whatsapp.templates.reenrollment_complete', [
            'name' => 'reenrollment_complete',
            'language' => 'id',
        ]);

        $registration = $this->makeRegistration(
            status: 'REENROLLED',
            name: 'Ahmad Fauzan'
        );

        $log = app(WhatsappNotificationService::class)
            ->reenrollmentComplete($registration);

        $this->assertSame(
            'REENROLLMENT_COMPLETE',
            $log->message_type
        );

        Queue::assertPushed(
            SendWhatsappTemplateJob::class,
            fn ($job) =>
                $job->templateName === 'reenrollment_complete'
        );
    }

    private function makeRegistration(
        string $status,
        string $name
    ): Registration {
        static $sequence = 0;

        $sequence++;

        $school = School::create([
            'name' => 'SMK WHATSAPP TEST '.$sequence,
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
            'code' => 'UMUM-'.$sequence,
            'is_active' => true,
        ]);

        $major = Major::create([
            'school_id' => $school->id,
            'code' => 'WT'.$sequence,
            'name' => 'JURUSAN WHATSAPP TEST '.$sequence,
            'is_active' => true,
        ]);

        return Registration::create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,

            'registration_number' => 'WA-TEST-'.$sequence,

            'nik' => '3311111111'.str_pad(
                (string) $sequence,
                6,
                '0',
                STR_PAD_LEFT
            ),

            'full_name' => $name,
            'origin_school' => 'SMP TEST',

            'whatsapp' => '08121111'.str_pad(
                (string) $sequence,
                4,
                '0',
                STR_PAD_LEFT
            ),

            'data_source' => 'ADMIN',
            'status' => $status,
            'registered_at' => now(),

            'accepted_at' =>
                in_array(
                    $status,
                    ['ACCEPTED', 'REENROLLED'],
                    true
                )
                    ? now()
                    : null,

            'reenrolled_at' =>
                $status === 'REENROLLED'
                    ? now()
                    : null,
        ]);
    }
}