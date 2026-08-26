<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use App\Services\RegistrationStatusService;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class RegistrationStatusTransactionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_status_change_rolls_back_when_activity_log_fails(): void
    {
        Queue::fake();

        [$registration, $user] = $this->makeRegistration();

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Simulated activity log failure.'
            );
        });

        try {
            app(RegistrationStatusService::class)->change(
                $registration,
                'ACCEPTED',
                $user,
                'Transaction rollback test.'
            );

            $this->fail(
                'Expected simulated activity log failure.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated activity log failure.',
                $exception->getMessage()
            );
        } finally {
            ActivityLog::flushEventListeners();
        }

        $registration->refresh();

        $this->assertSame(
            'REGISTERED',
            $registration->status
        );

        $this->assertNull(
            $registration->accepted_at
        );

        $this->assertDatabaseMissing(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'to_status' => 'ACCEPTED',
            ]
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'registration_id' => $registration->id,
                'action' => 'CHANGE_STATUS',
            ]
        );

        $this->assertDatabaseMissing(
            'whatsapp_logs',
            [
                'registration_id' => $registration->id,
                'message_type' => 'REGISTRATION_ACCEPTED',
            ]
        );
    }

    public function test_status_change_rolls_back_when_whatsapp_notification_fails(): void
    {
        Queue::fake();

        [$registration, $user] = $this->makeRegistration();

        $notificationService = $this->mock(
            WhatsappNotificationService::class
        );

        $notificationService
            ->shouldReceive('registrationAccepted')
            ->once()
            ->andThrow(
                new RuntimeException(
                    'Simulated WhatsApp notification failure.'
                )
            );

        try {
            app(RegistrationStatusService::class)->change(
                $registration,
                'ACCEPTED',
                $user,
                'WhatsApp rollback test.'
            );

            $this->fail(
                'Expected simulated WhatsApp notification failure.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated WhatsApp notification failure.',
                $exception->getMessage()
            );
        }

        $registration->refresh();

        $this->assertSame(
            'REGISTERED',
            $registration->status
        );

        $this->assertNull(
            $registration->accepted_at
        );

        $this->assertDatabaseMissing(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'to_status' => 'ACCEPTED',
            ]
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'registration_id' => $registration->id,
                'action' => 'CHANGE_STATUS',
            ]
        );

        $this->assertDatabaseMissing(
            'whatsapp_logs',
            [
                'registration_id' => $registration->id,
                'message_type' => 'REGISTRATION_ACCEPTED',
            ]
        );
    }

    private function makeRegistration(): array
    {
        $user = User::factory()->create([
            'name' => 'ADMIN TRANSACTION TEST',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::query()->create([
            'name' => 'SMK STATUS TRANSACTION TEST',
            'npsn' => '88888888',
        ]);

        $period = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'TX',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        $admissionPath = AdmissionPath::query()->create([
            'period_id' => $period->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $major = Major::query()->create([
            'school_id' => $school->id,
            'code' => 'TX',
            'name' => 'JURUSAN TRANSACTION TEST',
            'short_name' => 'TX',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $registration = Registration::query()->create([
            'period_id' => $period->id,
            'admission_path_id' => $admissionPath->id,
            'major_id' => $major->id,
            'registration_number' => 'TX-2027-0001',
            'nik' => '1234567890123456',
            'full_name' => 'SISWA TRANSACTION TEST',
            'whatsapp' => '081234567890',
            'data_source' => 'ADMIN',
            'status' => 'REGISTERED',
            'registered_at' => now(),
        ]);

        return [$registration, $user];
    }
}