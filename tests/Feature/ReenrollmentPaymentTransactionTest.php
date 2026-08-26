<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use App\Services\ReenrollmentPaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ReenrollmentPaymentTransactionTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private School $school;

    private PpdbPeriod $period;

    private AdmissionPath $admissionPath;

    private Major $major;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'PAYMENT TRANSACTION TEST',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'name' => 'SMK PAYMENT TRANSACTION TEST',
            'npsn' => '77665544',
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
            'number_prefix' => 'TRX',
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
            'code' => 'TRX',
            'name' => 'JURUSAN TRANSACTION TEST',
            'short_name' => 'TRX',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_payment_rolls_back_when_payment_activity_log_fails(): void
    {
        $registration = $this->makeRegistration();

        ActivityLog::creating(function (ActivityLog $log) {
            if ($log->action === 'REENROLLMENT_PAYMENT') {
                throw new RuntimeException(
                    'Forced payment activity log failure.'
                );
            }
        });

        try {
            app(ReenrollmentPaymentService::class)->addPayment(
                $registration,
                100000,
                $this->user
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced payment activity log failure.',
                $exception->getMessage()
            );
        } finally {
            ActivityLog::flushEventListeners();
        }

        $registration->refresh();

        $this->assertSame(
            'ACCEPTED',
            $registration->status
        );

        $this->assertSame(
            0,
            DB::table('reenrollment_payments')
                ->where(
                    'registration_id',
                    $registration->id
                )
                ->count()
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'registration_id' => $registration->id,
                'action' => 'REENROLLMENT_PAYMENT',
            ]
        );
    }

    public function test_full_payment_rolls_back_when_status_activity_log_fails(): void
    {
        $registration = $this->makeRegistration();

        ActivityLog::creating(function (ActivityLog $log) {
            if ($log->action === 'CHANGE_STATUS') {
                throw new RuntimeException(
                    'Forced status activity log failure.'
                );
            }
        });

        try {
            app(ReenrollmentPaymentService::class)->addPayment(
                $registration,
                250000,
                $this->user
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced status activity log failure.',
                $exception->getMessage()
            );
        } finally {
            ActivityLog::flushEventListeners();
        }

        $registration->refresh();

        $this->assertSame(
            'ACCEPTED',
            $registration->status
        );

        $this->assertNull(
            $registration->reenrolled_at
        );

        $this->assertSame(
            0,
            DB::table('reenrollment_payments')
                ->where(
                    'registration_id',
                    $registration->id
                )
                ->count()
        );

        $this->assertDatabaseMissing(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'to_status' => 'REENROLLED',
            ]
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'registration_id' => $registration->id,
                'action' => 'REENROLLMENT_PAYMENT',
            ]
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'registration_id' => $registration->id,
                'action' => 'CHANGE_STATUS',
            ]
        );
    }

    private function makeRegistration(): Registration
    {
        return Registration::query()->create([
            'period_id' => $this->period->id,
            'admission_path_id' => $this->admissionPath->id,
            'major_id' => $this->major->id,
            'registration_number' => 'TRX-2027-TRX-0001',
            'nik' => '3377665544000001',
            'full_name' => 'PENDAFTAR TRANSACTION TEST',
            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',
            'origin_school' => 'SMP TRANSACTION TEST',
            'whatsapp' => '081277665544',
            'data_source' => 'ADMIN',
            'status' => 'ACCEPTED',
            'created_by' => $this->user->id,
            'registered_at' => now(),
            'accepted_at' => now(),
        ]);
    }
}