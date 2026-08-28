<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\ReenrollmentPayment;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminReenrollmentPaymentReceiptTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

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

        $this->admin = User::factory()->create([
            'name' => 'ADMIN RECEIPT TEST',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'name' => 'SMK RECEIPT TEST',
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
            'number_prefix' => 'RCT',
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
            'code' => 'RCT',
            'name' => 'JURUSAN RECEIPT TEST',
            'short_name' => 'RCT',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_cannot_download_payment_receipt(): void
    {
        $registration = $this->makeRegistration();

        $payment = $this->makePayment(
            $registration,
            100000
        );

        $this->get(
            route(
                'admin.registrations.reenrollment-payments.receipt',
                [
                    'registration' => $registration,
                    'payment' => $payment,
                ]
            )
        )->assertRedirect(route('login'));
    }

    public function test_admin_can_download_payment_receipt(): void
    {
        $registration = $this->makeRegistration();

        $payment = $this->makePayment(
            $registration,
            100000
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'admin.registrations.reenrollment-payments.receipt',
                    [
                        'registration' => $registration,
                        'payment' => $payment,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );
    }

    public function test_payment_must_belong_to_registration(): void
    {
        $registrationA = $this->makeRegistration();

        $registrationB = $this->makeRegistration();

        $payment = $this->makePayment(
            $registrationA,
            100000
        );

        $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'admin.registrations.reenrollment-payments.receipt',
                    [
                        'registration' => $registrationB,
                        'payment' => $payment,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_receipt_uses_transaction_historical_balance(): void
    {
        $registration = $this->makeRegistration();

        $firstPayment = $this->makePayment(
            $registration,
            100000,
            '2027-02-10 08:00:00'
        );

        $this->makePayment(
            $registration,
            150000,
            '2027-02-15 09:00:00'
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'admin.registrations.reenrollment-payments.receipt',
                    [
                        'registration' => $registration,
                        'payment' => $firstPayment,
                    ]
                )
            );

        $response->assertOk();

        $this->assertSame(
            '100000',
            $response->headers->get(
                'x-receipt-payment-amount'
            )
        );

        $this->assertSame(
            '100000',
            $response->headers->get(
                'x-receipt-total-paid'
            )
        );

        $this->assertSame(
            '150000',
            $response->headers->get(
                'x-receipt-remaining'
            )
        );
    }

    public function test_second_receipt_contains_cumulative_paid_balance(): void
    {
        $registration = $this->makeRegistration();

        $this->makePayment(
            $registration,
            100000,
            '2027-02-10 08:00:00'
        );

        $secondPayment = $this->makePayment(
            $registration,
            150000,
            '2027-02-15 09:00:00'
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'admin.registrations.reenrollment-payments.receipt',
                    [
                        'registration' => $registration,
                        'payment' => $secondPayment,
                    ]
                )
            );

        $response->assertOk();

        $this->assertSame(
            '150000',
            $response->headers->get(
                'x-receipt-payment-amount'
            )
        );

        $this->assertSame(
            '250000',
            $response->headers->get(
                'x-receipt-total-paid'
            )
        );

        $this->assertSame(
            '0',
            $response->headers->get(
                'x-receipt-remaining'
            )
        );
    }

    public function test_receipt_can_be_printed_for_closed_period(): void
    {
        $registration = $this->makeRegistration();

        $payment = $this->makePayment(
            $registration,
            100000
        );

        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'admin.registrations.reenrollment-payments.receipt',
                    [
                        'registration' => $registration,
                        'payment' => $payment,
                    ]
                )
            )
            ->assertOk();
    }

    public function test_receipt_has_stable_filename(): void
    {
        $registration = $this->makeRegistration();

        $payment = $this->makePayment(
            $registration,
            100000
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'admin.registrations.reenrollment-payments.receipt',
                    [
                        'registration' => $registration,
                        'payment' => $payment,
                    ]
                )
            );

        $disposition = $response->headers->get(
            'content-disposition'
        );

        $this->assertNotNull($disposition);

        $receiptNumber = sprintf(
            'DU-%d-%06d',
            (int) $this->period->year_start,
            (int) $payment->id
        );

        $this->assertStringContainsString(
            'bukti-pembayaran-'
                .$receiptNumber
                .'.pdf',
            $disposition
        );
    }

    private function makeRegistration(): Registration
    {
        static $sequence = 0;

        $sequence++;

        return Registration::query()->create([
            'period_id' => $this->period->id,
            'admission_path_id' => $this->admissionPath->id,
            'major_id' => $this->major->id,
            'registration_number' =>
                'RCT-2027-'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),
            'nik' =>
                '3374010101'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),
            'full_name' =>
                'PENDAFTAR RECEIPT '.$sequence,
            'whatsapp' =>
                '081200'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),
            'data_source' => 'ADMIN',
            'status' => 'ACCEPTED',
            'registered_at' => now(),
        ]);
    }

    private function makePayment(
        Registration $registration,
        int $amount,
        ?string $paidAt = null
    ): ReenrollmentPayment {
        return ReenrollmentPayment::query()->create([
            'registration_id' => $registration->id,
            'amount' => $amount,
            'paid_at' => $paidAt
                ? Carbon::parse(
                    $paidAt,
                    config('app.timezone')
                )
                : now(),
            'payment_method' => 'CASH',
            'reference_number' => null,
            'received_by' => $this->admin->id,
            'notes' => 'Payment receipt test.',
        ]);
    }
}