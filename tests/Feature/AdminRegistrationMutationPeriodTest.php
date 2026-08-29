<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRegistrationMutationPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private PpdbPeriod $historicalPeriod;

    private PpdbPeriod $activePeriod;

    private Major $major;

    private AdmissionPath $historicalPath;

    private AdmissionPath $activePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'name' => 'SMK MUTATION PERIOD TEST',
        ]);

        $this->historicalPeriod = $this->makePeriod(
            '2026/2027',
            2026,
            false,
            'CLOSED'
        );

        $this->activePeriod = $this->makePeriod(
            '2027/2028',
            2027,
            true,
            'OPEN'
        );

        $this->major = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'is_active' => true,
        ]);

        $this->historicalPath = $this->makePath(
            $this->historicalPeriod,
            'KHUSUS-HIST'
        );

        $this->activePath = $this->makePath(
            $this->activePeriod,
            'UMUM-ACTIVE'
        );
    }

    public function test_status_mutation_with_matching_explicit_period_is_allowed(): void
    {
        $registration = $this->makeRegistration(
            $this->activePeriod,
            $this->activePath,
            'REGISTERED'
        );

        $this->actingAs($this->admin)
            ->patch(
                route('admin.registrations.status.update', [
                    'registration' => $registration,
                    'period_id' => $this->activePeriod->id,
                ]),
                [
                    'status' => 'ACCEPTED',
                    'notes' => 'Period context test.',
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => 'ACCEPTED',
        ]);
    }

    public function test_status_mutation_with_mismatched_period_returns_404_and_does_not_change_data(): void
    {
        $registration = $this->makeRegistration(
            $this->historicalPeriod,
            $this->historicalPath,
            'REGISTERED'
        );

        $this->actingAs($this->admin)
            ->patch(
                route('admin.registrations.status.update', [
                    'registration' => $registration,
                    'period_id' => $this->activePeriod->id,
                ]),
                [
                    'status' => 'ACCEPTED',
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => 'REGISTERED',
        ]);

        $this->assertDatabaseMissing(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'to_status' => 'ACCEPTED',
            ]
        );
    }

    public function test_payment_with_matching_explicit_period_is_allowed(): void
    {
        $registration = $this->makeRegistration(
            $this->activePeriod,
            $this->activePath,
            'ACCEPTED'
        );

        $this->actingAs($this->admin)
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    [
                        'registration' => $registration,
                        'period_id' => $this->activePeriod->id,
                    ]
                ),
                [
                    'amount' => 100000,
                    'payment_method' => 'CASH',
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
                'amount' => 100000,
            ]
        );
    }

    public function test_payment_with_mismatched_period_returns_404_and_does_not_create_payment(): void
    {
        $registration = $this->makeRegistration(
            $this->historicalPeriod,
            $this->historicalPath,
            'ACCEPTED'
        );

        $this->actingAs($this->admin)
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    [
                        'registration' => $registration,
                        'period_id' => $this->activePeriod->id,
                    ]
                ),
                [
                    'amount' => 100000,
                    'payment_method' => 'CASH',
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseMissing(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
            ]
        );

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => 'ACCEPTED',
        ]);
    }

    public function test_status_mutation_on_historical_period_is_rejected_and_does_not_change_data(): void
    {
        $registration = $this->makeRegistration(
            $this->historicalPeriod,
            $this->historicalPath,
            'REGISTERED'
        );

        $response = $this->actingAs($this->admin)
            ->patch(
                route(
                    'admin.registrations.status.update',
                    $registration
                ),
                [
                    'status' => 'ACCEPTED',
                    'notes' => 'Historical period must be read-only.',
                ]
            );

        $response->assertSessionHasErrors();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => 'REGISTERED',
        ]);

        $this->assertDatabaseMissing(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'to_status' => 'ACCEPTED',
            ]
        );
    }

    public function test_payment_on_historical_period_is_rejected_and_does_not_create_payment(): void
    {
        $registration = $this->makeRegistration(
            $this->historicalPeriod,
            $this->historicalPath,
            'ACCEPTED'
        );

        $response = $this->actingAs($this->admin)
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

        $response->assertSessionHasErrors();

        $this->assertDatabaseMissing(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
            ]
        );

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => 'ACCEPTED',
        ]);
    }

    public function test_reenrolled_status_cannot_be_set_manually_through_status_endpoint(): void
    {
        $registration = $this->makeRegistration(
            $this->activePeriod,
            $this->activePath,
            'ACCEPTED'
        );

        $this->actingAs($this->admin)
            ->patch(
                route(
                    'admin.registrations.status.update',
                    [
                        'registration' => $registration,
                        'period_id' => $this->activePeriod->id,
                    ]
                ),
                [
                    'status' => 'REENROLLED',
                    'notes' => 'Tidak boleh daftar ulang manual.',
                ]
            )
            ->assertSessionHasErrors('status');

        $registration->refresh();

        $this->assertSame(
            'ACCEPTED',
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

    private function makePeriod(
        string $name,
        int $yearStart,
        bool $isActive,
        string $status
    ): PpdbPeriod {
        return PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => $name,
            'year_start' => $yearStart,
            'year_end' => $yearStart + 1,
            'registration_open' => $yearStart.'-01-01',
            'registration_close' => $yearStart.'-06-30',
            'status' => $status,
            'is_active' => $isActive,
            'number_prefix' => 'MARSA',
            'number_year' => $yearStart,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 350000,
        ]);
    }

    private function makePath(
        PpdbPeriod $period,
        string $code
    ): AdmissionPath {
        return AdmissionPath::query()->create([
            'period_id' => $period->id,
            'name' => $code,
            'code' => $code,
            'start_date' => $period->year_start.'-01-01',
            'end_date' => $period->year_start.'-06-30',
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    private function makeRegistration(
        PpdbPeriod $period,
        AdmissionPath $path,
        string $status
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::query()->create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $this->major->id,

            'registration_number' =>
                'MUT-PERIOD-'
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

            'full_name' =>
                'MUTATION PERIOD TEST '.$sequence,

            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',

            'origin_school' =>
                'SMP MUTATION PERIOD TEST',

            'whatsapp' =>
                '08129999'
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
                $status === 'ACCEPTED'
                    ? now()
                    : null,

            'notes' => null,
        ]);
    }
}