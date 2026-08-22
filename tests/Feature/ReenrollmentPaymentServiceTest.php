<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use App\Services\ReenrollmentPaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class ReenrollmentPaymentServiceTest extends TestCase
{
    use DatabaseTransactions;

    private ReenrollmentPaymentService $service;

    private User $user;

    private School $school;

    private PpdbPeriod $period;

    private AdmissionPath $admissionPath;

    private Major $major;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::parse(
                '2027-02-15 10:00:00',
                config('app.timezone')
            )
        );

        $this->service = app(
            ReenrollmentPaymentService::class
        );

        $this->user = User::factory()->create([
            'name' => 'BENDAHARA TEST',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'name' => 'SMK PAYMENT TEST',
            'npsn' => '88888888',
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
            'number_prefix' => 'PAY',
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
            'code' => 'PAY',
            'name' => 'JURUSAN PAYMENT TEST',
            'short_name' => 'PAY',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_accepted_registration_can_make_partial_payment(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $payment = $this->service->addPayment(
            $registration,
            100000,
            $this->user,
            'CASH',
            null,
            'Pembayaran tahap pertama.'
        );

        $this->assertSame(
            100000,
            $payment->amount
        );

        $this->assertSame(
            $registration->id,
            $payment->registration_id
        );

        $this->assertSame(
            $this->user->id,
            $payment->received_by
        );

        $this->assertSame(
            'CASH',
            $payment->payment_method
        );

        $registration->refresh();

        $this->assertSame(
            'ACCEPTED',
            $registration->status
        );

        $this->assertDatabaseHas(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
                'amount' => 100000,
                'received_by' => $this->user->id,
                'payment_method' => 'CASH',
                'notes' => 'Pembayaran tahap pertama.',
            ]
        );
    }

    public function test_partial_payment_does_not_change_status_to_reenrolled(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $this->service->addPayment(
            $registration,
            100000,
            $this->user
        );

        $registration->refresh();

        $this->assertSame(
            'ACCEPTED',
            $registration->status
        );

        $this->assertNull(
            $registration->reenrolled_at
        );

        $this->assertDatabaseMissing(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'to_status' => 'REENROLLED',
            ]
        );
    }

    public function test_full_payment_automatically_changes_status_to_reenrolled(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $this->service->addPayment(
            $registration,
            250000,
            $this->user,
            'TRANSFER',
            'TRX-TEST-001',
            'Pelunasan daftar ulang.'
        );

        $registration->refresh();

        $this->assertSame(
            'REENROLLED',
            $registration->status
        );

        $this->assertNotNull(
            $registration->reenrolled_at
        );

        $this->assertDatabaseHas(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'from_status' => 'ACCEPTED',
                'to_status' => 'REENROLLED',
                'changed_by' => $this->user->id,
            ]
        );
    }

    public function test_multiple_payments_can_complete_reenrollment(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $this->service->addPayment(
            $registration,
            100000,
            $this->user
        );

        $registration->refresh();

        $this->assertSame(
            'ACCEPTED',
            $registration->status
        );

        $this->service->addPayment(
            $registration,
            150000,
            $this->user
        );

        $registration->refresh();

        $this->assertSame(
            'REENROLLED',
            $registration->status
        );

        $this->assertSame(
            250000,
            (int) DB::table('reenrollment_payments')
                ->where(
                    'registration_id',
                    $registration->id
                )
                ->sum('amount')
        );
    }

    public function test_payment_above_remaining_amount_is_rejected(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $this->service->addPayment(
            $registration,
            100000,
            $this->user
        );

        try {
            $this->service->addPayment(
                $registration->fresh(),
                150001,
                $this->user
            );

            $this->fail(
                'Pembayaran melebihi sisa seharusnya ditolak.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertNotEmpty(
                $exception->getMessage()
            );
        }

        $this->assertSame(
            100000,
            (int) DB::table('reenrollment_payments')
                ->where(
                    'registration_id',
                    $registration->id
                )
                ->sum('amount')
        );

        $registration->refresh();

        $this->assertSame(
            'ACCEPTED',
            $registration->status
        );
    }

    public function test_registered_registration_cannot_make_reenrollment_payment(): void
    {
        $registration = $this->makeRegistration(
            'REGISTERED'
        );

        try {
            $this->service->addPayment(
                $registration,
                100000,
                $this->user
            );

            $this->fail(
                'REGISTERED seharusnya belum boleh membayar daftar ulang.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertNotEmpty(
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
            ]
        );
    }

    public function test_rejected_registration_cannot_make_reenrollment_payment(): void
    {
        $registration = $this->makeRegistration(
            'REJECTED'
        );

        try {
            $this->service->addPayment(
                $registration,
                100000,
                $this->user
            );

            $this->fail(
                'REJECTED seharusnya tidak boleh membayar daftar ulang.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertNotEmpty(
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
            ]
        );
    }

    public function test_zero_payment_is_rejected(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        try {
            $this->service->addPayment(
                $registration,
                0,
                $this->user
            );

            $this->fail(
                'Pembayaran nol seharusnya ditolak.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertNotEmpty(
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
            ]
        );
    }

    public function test_negative_payment_is_rejected(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        try {
            $this->service->addPayment(
                $registration,
                -1000,
                $this->user
            );

            $this->fail(
                'Pembayaran negatif seharusnya ditolak.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertNotEmpty(
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
            ]
        );
    }

    public function test_payment_details_are_stored(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $payment = $this->service->addPayment(
            $registration,
            50000,
            $this->user,
            'TRANSFER',
            'REF-2027-001',
            'Transfer bank.'
        );

        $this->assertSame(
            'TRANSFER',
            $payment->payment_method
        );

        $this->assertSame(
            'REF-2027-001',
            $payment->reference_number
        );

        $this->assertSame(
            'Transfer bank.',
            $payment->notes
        );

        $this->assertSame(
            $this->user->id,
            $payment->received_by
        );

        $this->assertNotNull(
            $payment->paid_at
        );
    }

    public function test_payment_creates_activity_log(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $this->service->addPayment(
            $registration,
            100000,
            $this->user
        );

        $log = DB::table('activity_logs')
            ->where(
                'registration_id',
                $registration->id
            )
            ->where(
                'user_id',
                $this->user->id
            )
            ->latest('id')
            ->first();

        $this->assertNotNull($log);

        $this->assertNotEmpty(
            $log->action
        );

        $metadata = json_decode(
            $log->metadata,
            true
        );

        $this->assertIsArray(
            $metadata
        );

        $this->assertSame(
            100000,
            (int) $metadata['amount']
        );
    }

    public function test_fee_helpers_return_correct_values(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $this->assertSame(
            250000,
            $this->service->requiredFee(
                $registration
            )
        );

        $this->assertSame(
            0,
            $this->service->totalPaid(
                $registration
            )
        );

        $this->assertSame(
            250000,
            $this->service->remaining(
                $registration
            )
        );

        $this->assertFalse(
            $this->service->isPaidOff(
                $registration
            )
        );

        $this->service->addPayment(
            $registration,
            100000,
            $this->user
        );

        $registration = $registration->fresh();

        $this->assertSame(
            100000,
            $this->service->totalPaid(
                $registration
            )
        );

        $this->assertSame(
            150000,
            $this->service->remaining(
                $registration
            )
        );

        $this->assertFalse(
            $this->service->isPaidOff(
                $registration
            )
        );
    }

    private function makeRegistration(
        string $status
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::query()->create([
            'period_id' => $this->period->id,
            'wave_id' => null,
            'admission_path_id' =>
                $this->admissionPath->id,
            'major_id' => $this->major->id,

            'registration_number' =>
                'PAYMENT-TEST-'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'nik' =>
                '3388888888'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'nisn' => null,

            'full_name' =>
                'PENDAFTAR PAYMENT TEST '.$sequence,

            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',

            'origin_school' => 'SMP PAYMENT TEST',

            'whatsapp' =>
                '08128888'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'data_source' => 'ADMIN',
            'status' => $status,
            'created_by' => $this->user->id,

            'registered_at' => now(),

            'accepted_at' =>
                in_array(
                    $status,
                    ['ACCEPTED', 'REENROLLED'],
                    true
                )
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
        ]);
    }
}
