<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\Registration;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Jobs\SendWhatsappTemplateJob;
use App\Services\RegistrationStatusService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

class RegistrationStatusServiceTest extends TestCase
{
    use DatabaseTransactions;

    private RegistrationStatusService $service;

    private User $user;

    private School $school;

    private PpdbPeriod $period;

    private AdmissionPath $admissionPath;

    private Major $major;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        Carbon::setTestNow(
            Carbon::parse(
                '2027-02-15 10:00:00',
                config('app.timezone')
            )
        );

        $this->service = app(
            RegistrationStatusService::class
        );

        $this->user = User::factory()->create([
            'name' => 'ADMIN STATUS TEST',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'name' => 'SMK STATUS TEST',
            'npsn' => '99999999',
        ]);

        $this->period = PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'TEST',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        $this->admissionPath = AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->major = Major::query()->create([
            'school_id' => $this->school->id,
            'code' => 'TST',
            'name' => 'JURUSAN TEST',
            'short_name' => 'TST',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_registered_can_be_accepted(): void
    {
        $registration = $this->makeRegistration(
            'REGISTERED'
        );

        $result = $this->service->change(
            $registration,
            'ACCEPTED',
            $this->user,
            'Diterima melalui feature test.'
        );

        $this->assertSame(
            'ACCEPTED',
            $result->status
        );

        $this->assertNotNull(
            $result->accepted_at
        );

        $this->assertSame(
            '2027-02-15 10:00:00',
            $result->accepted_at->format(
                'Y-m-d H:i:s'
            )
        );

        $this->assertDatabaseHas(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'from_status' => 'REGISTERED',
                'to_status' => 'ACCEPTED',
                'changed_by' => $this->user->id,
                'notes' => 'Diterima melalui feature test.',
            ]
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'registration_id' => $registration->id,
                'user_id' => $this->user->id,
                'action' => 'CHANGE_STATUS',
            ]
        );
        $whatsappLog = WhatsappLog::query()
            ->where('registration_id', $registration->id)
            ->where('message_type', 'REGISTRATION_ACCEPTED')
            ->firstOrFail();

        $this->assertSame('PENDING', $whatsappLog->status);

        Queue::assertPushed(
            SendWhatsappTemplateJob::class,
            fn ($job) =>
                $job->whatsappLogId === $whatsappLog->id
                && $job->templateName === 'registration_accepted'
                && $job->languageCode === 'id'
        );
    }

    public function test_registered_can_be_rejected(): void
    {
        $registration = $this->makeRegistration(
            'REGISTERED'
        );

        $result = $this->service->change(
            $registration,
            'REJECTED',
            $this->user,
            'Tidak lolos seleksi.'
        );

        $this->assertSame(
            'REJECTED',
            $result->status
        );

        $this->assertNotNull(
            $result->rejected_at
        );

        $this->assertSame(
            '2027-02-15 10:00:00',
            $result->rejected_at->format(
                'Y-m-d H:i:s'
            )
        );

        $this->assertDatabaseHas(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'from_status' => 'REGISTERED',
                'to_status' => 'REJECTED',
                'changed_by' => $this->user->id,
                'notes' => 'Tidak lolos seleksi.',
            ]
        );
        $whatsappLog = WhatsappLog::query()
            ->where('registration_id', $registration->id)
            ->where('message_type', 'REGISTRATION_REJECTED')
            ->firstOrFail();

        $this->assertSame('PENDING', $whatsappLog->status);

        Queue::assertPushed(
            SendWhatsappTemplateJob::class,
            fn ($job) =>
                $job->whatsappLogId === $whatsappLog->id
                && $job->templateName === 'registration_rejected'
                && $job->languageCode === 'id'
        );
    }

    public function test_registered_can_be_withdrawn(): void
    {
        $registration = $this->makeRegistration(
            'REGISTERED'
        );

        $result = $this->service->change(
            $registration,
            'WITHDRAWN',
            $this->user,
            'Calon siswa mengundurkan diri.'
        );

        $this->assertSame(
            'WITHDRAWN',
            $result->status
        );

        $this->assertNotNull(
            $result->withdrawn_at
        );

        $this->assertSame(
            '2027-02-15 10:00:00',
            $result->withdrawn_at->format(
                'Y-m-d H:i:s'
            )
        );

        $this->assertDatabaseHas(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'from_status' => 'REGISTERED',
                'to_status' => 'WITHDRAWN',
                'changed_by' => $this->user->id,
            ]
        );
        $whatsappLog = WhatsappLog::query()
            ->where('registration_id', $registration->id)
            ->where('message_type', 'REGISTRATION_WITHDRAWN')
            ->firstOrFail();

        $this->assertSame('PENDING', $whatsappLog->status);

        Queue::assertPushed(
            SendWhatsappTemplateJob::class,
            fn ($job) =>
                $job->whatsappLogId === $whatsappLog->id
                && $job->templateName === 'registration_withdrawn'
                && $job->languageCode === 'id'
        );
    }

    public function test_accepted_can_be_withdrawn(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $result = $this->service->change(
            $registration,
            'WITHDRAWN',
            $this->user,
            'Mengundurkan diri setelah diterima.'
        );

        $this->assertSame(
            'WITHDRAWN',
            $result->status
        );

        $this->assertNotNull(
            $result->withdrawn_at
        );

        $this->assertDatabaseHas(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'from_status' => 'ACCEPTED',
                'to_status' => 'WITHDRAWN',
            ]
        );
    }

    public function test_reenrolled_can_be_withdrawn(): void
    {
        $registration = $this->makeRegistration(
            'REENROLLED'
        );

        $result = $this->service->change(
            $registration,
            'WITHDRAWN',
            $this->user,
            'Mengundurkan diri setelah menyelesaikan daftar ulang.'
        );

        $this->assertSame(
            'WITHDRAWN',
            $result->status
        );

        $this->assertNotNull(
            $result->withdrawn_at
        );

        $this->assertDatabaseHas(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'from_status' => 'REENROLLED',
                'to_status' => 'WITHDRAWN',
                'changed_by' => $this->user->id,
            ]
        );

        $whatsappLog = WhatsappLog::query()
            ->where('registration_id', $registration->id)
            ->where('message_type', 'REGISTRATION_WITHDRAWN')
            ->firstOrFail();

        $this->assertSame(
            'PENDING',
            $whatsappLog->status
        );

        Queue::assertPushed(
            SendWhatsappTemplateJob::class,
            fn ($job) =>
                $job->whatsappLogId === $whatsappLog->id
                && $job->templateName === 'registration_withdrawn'
                && $job->languageCode === 'id'
        );
    }

    public function test_registered_cannot_be_changed_directly_to_reenrolled(): void
    {
        $registration = $this->makeRegistration(
            'REGISTERED'
        );

        try {
            $this->service->change(
                $registration,
                'REENROLLED',
                $this->user
            );

            $this->fail(
                'REGISTERED seharusnya tidak dapat langsung menjadi REENROLLED.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'tidak diizinkan',
                $exception->getMessage()
            );
        }

        $registration->refresh();

        $this->assertSame(
            'REGISTERED',
            $registration->status
        );

        $this->assertDatabaseMissing(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'to_status' => 'REENROLLED',
            ]
        );
    }

    public function test_rejected_cannot_be_changed_to_accepted(): void
    {
        $registration = $this->makeRegistration(
            'REJECTED'
        );

        try {
            $this->service->change(
                $registration,
                'ACCEPTED',
                $this->user
            );

            $this->fail(
                'REJECTED seharusnya merupakan status terminal.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'tidak diizinkan',
                $exception->getMessage()
            );
        }

        $registration->refresh();

        $this->assertSame(
            'REJECTED',
            $registration->status
        );
    }

    public function test_same_status_transition_is_rejected(): void
    {
        $registration = $this->makeRegistration(
            'REGISTERED'
        );

        try {
            $this->service->change(
                $registration,
                'REGISTERED',
                $this->user
            );

            $this->fail(
                'Perubahan ke status yang sama seharusnya ditolak.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'sudah REGISTERED',
                $exception->getMessage()
            );
        }

        $registration->refresh();

        $this->assertSame(
            'REGISTERED',
            $registration->status
        );
    }

    public function test_status_change_records_actor_in_history(): void
    {
        $registration = $this->makeRegistration(
            'REGISTERED'
        );

        $this->service->change(
            $registration,
            'ACCEPTED',
            $this->user,
            'Test actor.'
        );

        $history = DB::table(
            'registration_status_histories'
        )
            ->where(
                'registration_id',
                $registration->id
            )
            ->where(
                'to_status',
                'ACCEPTED'
            )
            ->first();

        $this->assertNotNull($history);

        $this->assertSame(
            $this->user->id,
            (int) $history->changed_by
        );
    }

    public function test_status_change_creates_activity_log_with_metadata(): void
    {
        $registration = $this->makeRegistration(
            'REGISTERED'
        );

        $this->service->change(
            $registration,
            'ACCEPTED',
            $this->user
        );

        $log = DB::table('activity_logs')
            ->where(
                'registration_id',
                $registration->id
            )
            ->where(
                'action',
                'CHANGE_STATUS'
            )
            ->latest('id')
            ->first();

        $this->assertNotNull($log);

        $this->assertSame(
            $this->user->id,
            (int) $log->user_id
        );

        $metadata = json_decode(
            $log->metadata,
            true
        );

        $this->assertSame(
            'REGISTERED',
            $metadata['from_status']
        );

        $this->assertSame(
            'ACCEPTED',
            $metadata['to_status']
        );

        $this->assertSame(
            $registration->registration_number,
            $metadata['registration_number']
        );
    }

    private function makeRegistration(
        string $status
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::query()->create([
            'period_id' => $this->period->id,
            'admission_path_id' => $this->admissionPath->id,
            'major_id' => $this->major->id,

            'registration_number' =>
                'STATUS-TEST-'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'nik' =>
                '3399999999'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'nisn' => null,

            'full_name' =>
                'PENDAFTAR STATUS TEST '.$sequence,

            'birth_place' => null,
            'birth_date' => null,
            'gender' => null,
            'religion' => null,

            'origin_school' =>
                'SMP STATUS TEST',

            'hamlet' => null,
            'rt' => null,
            'rw' => null,
            'village' => null,
            'district' => null,
            'city' => null,
            'province' => null,
            'postal_code' => null,

            'father_name' => null,
            'mother_name' => null,
            'father_job' => null,
            'mother_job' => null,

            'whatsapp' =>
                '08129999'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'graduation_score' => null,
            'achievement_relief' => null,
            'referrer_name' => null,
            'referrer_source' => null,

            'data_source' => 'ADMIN',
            'status' => $status,

            'created_by' => $this->user->id,

            'registered_at' => now(),

            'accepted_at' =>
                $status === 'ACCEPTED'
                    ? now()
                    : null,

            'rejected_at' =>
                $status === 'REJECTED'
                    ? now()
                    : null,

            'reenrolled_at' =>
                $status === 'REENROLLED'
                    ? now()
                    : null,

            'withdrawn_at' =>
                $status === 'WITHDRAWN'
                    ? now()
                    : null,

            'notes' => null,
        ]);
    }
}
