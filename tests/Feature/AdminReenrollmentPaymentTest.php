<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminReenrollmentPaymentTest extends TestCase
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
            'name' => 'ADMIN PAYMENT TEST',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'name' => 'SMK ADMIN PAYMENT TEST',
            'npsn' => '77777777',
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
            'number_prefix' => 'ADM',
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
            'code' => 'ADM',
            'name' => 'JURUSAN ADMIN PAYMENT TEST',
            'short_name' => 'ADM',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_cannot_store_reenrollment_payment(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $response = $this->post(
            route(
                'admin.registrations.reenrollment-payments.store',
                $registration
            ),
            [
                'amount' => 100000,
                'payment_method' => 'CASH',
            ]
        );

        $response->assertRedirect(
            route('login')
        );

        $this->assertDatabaseMissing(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
            ]
        );
    }

    public function test_admin_can_store_partial_reenrollment_payment(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $response = $this
            ->actingAs($this->admin)
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    $registration
                ),
                [
                    'amount' => '100.000',
                    'payment_method' => 'CASH',
                    'reference_number' => null,
                    'notes' => 'Pembayaran pertama.',
                ]
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.registrations.show',
                    $registration
                )
            );

        $this->assertDatabaseHas(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
                'amount' => 100000,
                'payment_method' => 'CASH',
                'received_by' => $this->admin->id,
                'notes' => 'Pembayaran pertama.',
            ]
        );

        $registration->refresh();

        $this->assertSame(
            'ACCEPTED',
            $registration->status
        );

        $this->assertNull(
            $registration->reenrolled_at
        );

        $response->assertSessionHas(
            'success',
            'Pembayaran berhasil dicatat. Sisa tagihan: Rp 150.000.'
        );
    }

    public function test_final_payment_automatically_changes_status_to_reenrolled(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        DB::table('reenrollment_payments')->insert([
            'registration_id' => $registration->id,
            'amount' => 100000,
            'paid_at' => now(),
            'payment_method' => 'CASH',
            'reference_number' => null,
            'received_by' => $this->admin->id,
            'notes' => 'Pembayaran pertama.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    $registration
                ),
                [
                    'amount' => '150000',
                    'payment_method' => 'TRANSFER',
                    'reference_number' => 'TRX-PELUNASAN-001',
                    'notes' => 'Pelunasan.',
                ]
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.registrations.show',
                    $registration
                )
            );

        $registration->refresh();

        $this->assertSame(
            'REENROLLED',
            $registration->status
        );

        $this->assertNotNull(
            $registration->reenrolled_at
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

        $this->assertDatabaseHas(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'from_status' => 'ACCEPTED',
                'to_status' => 'REENROLLED',
                'changed_by' => $this->admin->id,
            ]
        );

        $response->assertSessionHas(
            'success',
            'Pembayaran berhasil dicatat. Biaya daftar ulang telah lunas dan status pendaftar otomatis menjadi Daftar Ulang.'
        );
    }

    public function test_payment_above_remaining_amount_is_rejected(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        DB::table('reenrollment_payments')->insert([
            'registration_id' => $registration->id,
            'amount' => 100000,
            'paid_at' => now(),
            'payment_method' => 'CASH',
            'reference_number' => null,
            'received_by' => $this->admin->id,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->from(
                route(
                    'admin.registrations.show',
                    $registration
                )
            )
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    $registration
                ),
                [
                    'amount' => 150001,
                    'payment_method' => 'CASH',
                ]
            );

        $response->assertRedirect(
            route(
                'admin.registrations.show',
                $registration
            )
        );

        $response->assertSessionHasErrors(
            'payment'
        );

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

    public function test_registered_student_cannot_make_reenrollment_payment(): void
    {
        $registration = $this->makeRegistration(
            'REGISTERED'
        );

        $response = $this
            ->actingAs($this->admin)
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    $registration
                ),
                [
                    'amount' => 100000,
                    'payment_method' => 'CASH',
                ]
            );

        $response->assertSessionHasErrors(
            'payment'
        );

        $this->assertDatabaseMissing(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
            ]
        );

        $registration->refresh();

        $this->assertSame(
            'REGISTERED',
            $registration->status
        );
    }

    public function test_zero_amount_is_rejected_by_request_validation(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $response = $this
            ->actingAs($this->admin)
            ->from(
                route(
                    'admin.registrations.show',
                    $registration
                )
            )
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    $registration
                ),
                [
                    'amount' => '0',
                    'payment_method' => 'CASH',
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'admin.registrations.show',
                    $registration
                )
            )
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
            ]
        );
    }

    public function test_invalid_payment_method_is_rejected(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $response = $this
            ->actingAs($this->admin)
            ->from(
                route(
                    'admin.registrations.show',
                    $registration
                )
            )
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    $registration
                ),
                [
                    'amount' => 50000,
                    'payment_method' => 'BITCOIN',
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'admin.registrations.show',
                    $registration
                )
            )
            ->assertSessionHasErrors(
                'payment_method'
            );

        $this->assertDatabaseMissing(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
            ]
        );
    }

    public function test_payment_metadata_and_receiver_are_saved(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $this->actingAs($this->admin)
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    $registration
                ),
                [
                    'amount' => '50.000',
                    'payment_method' => 'transfer',
                    'reference_number' => ' REF-TEST-001 ',
                    'notes' => ' Transfer bank test. ',
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
                'amount' => 50000,
                'payment_method' => 'TRANSFER',
                'reference_number' => 'REF-TEST-001',
                'received_by' => $this->admin->id,
                'notes' => 'Transfer bank test.',
            ]
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'registration_id' => $registration->id,
                'user_id' => $this->admin->id,
                'action' => 'REENROLLMENT_PAYMENT',
            ]
        );
    }

    public function test_full_payment_records_audit_context_on_payment_and_status_logs(): void
    {
        $registration = $this->makeRegistration(
            'ACCEPTED'
        );

        $this->actingAs($this->admin)
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.30',
                'HTTP_USER_AGENT' =>
                    'SPMB-MARSA-Payment-Audit-Test/1.0',
            ])
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    $registration
                ),
                [
                    'amount' => 250000,
                    'payment_method' => 'CASH',
                    'notes' => 'Pelunasan audit context.',
                ]
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.registrations.show',
                    $registration
                )
            );

        $registration->refresh();

        $this->assertSame(
            'REENROLLED',
            $registration->status
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'user_id' => $this->admin->id,
                'registration_id' => $registration->id,
                'action' => 'REENROLLMENT_PAYMENT',
                'ip_address' => '203.0.113.30',
                'user_agent' =>
                    'SPMB-MARSA-Payment-Audit-Test/1.0',
            ]
        );

        $this->assertDatabaseHas(
        'activity_logs',
        [
            'user_id' => $this->admin->id,
            'registration_id' => $registration->id,
            'action' => 'CHANGE_STATUS',
            'ip_address' => '203.0.113.30',
            'user_agent' =>
                'SPMB-MARSA-Payment-Audit-Test/1.0',
        ]
    );
}

    private function makeRegistration(
        string $status
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::query()->create([
            'period_id' => $this->period->id,
            'admission_path_id' =>
                $this->admissionPath->id,
            'major_id' => $this->major->id,

            'registration_number' =>
                'ADMIN-PAY-'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'nik' =>
                '3377777777'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'nisn' => null,

            'full_name' =>
                'ADMIN PAYMENT TEST '.$sequence,

            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',

            'origin_school' =>
                'SMP ADMIN PAYMENT TEST',

            'whatsapp' =>
                '08127777'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'data_source' => 'ADMIN',
            'status' => $status,
            'created_by' => $this->admin->id,

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

            'notes' => null,
        ]);
    }
}