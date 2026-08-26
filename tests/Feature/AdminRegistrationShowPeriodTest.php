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

class AdminRegistrationShowPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private PpdbPeriod $historicalPeriod;

    private PpdbPeriod $activePeriod;

    private AdmissionPath $admissionPath;

    private Major $major;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin Period Show Test',
            'email' => 'admin-period-show@example.test',
            'password' => bcrypt('password'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'name' => 'SMK TEST PERIOD SHOW',
            'npsn' => '12345678',
        ]);

        $this->historicalPeriod = $this->makePeriod(
            '2026/2027',
            2026,
            2027,
            false,
            'CLOSED'
        );

        $this->activePeriod = $this->makePeriod(
            '2027/2028',
            2027,
            2028,
            true,
            'OPEN'
        );

        $this->admissionPath = AdmissionPath::query()->create([
            'period_id' => $this->activePeriod->id,
            'name' => 'Umum',
            'code' => 'UMUM',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->major = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'is_active' => true,
        ]);
    }

    public function test_historical_registration_can_be_opened_with_matching_explicit_period(): void
    {
        $registration = $this->makeRegistration(
            $this->historicalPeriod
        );

        $this->actingAs($this->admin)
            ->get(route('admin.registrations.show', [
                'registration' => $registration,
                'period_id' => $this->historicalPeriod->id,
            ]))
            ->assertOk();
    }

    public function test_active_registration_can_be_opened_without_explicit_period(): void
    {
        $registration = $this->makeRegistration(
            $this->activePeriod
        );

        $this->actingAs($this->admin)
            ->get(route(
                'admin.registrations.show',
                $registration
            ))
            ->assertOk();
    }

    public function test_historical_registration_cannot_be_opened_in_active_period_context(): void
    {
        $registration = $this->makeRegistration(
            $this->historicalPeriod
        );

        $this->actingAs($this->admin)
            ->get(route('admin.registrations.show', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]))
            ->assertNotFound();
    }

    public function test_active_registration_cannot_be_opened_in_historical_period_context(): void
    {
        $registration = $this->makeRegistration(
            $this->activePeriod
        );

        $this->actingAs($this->admin)
            ->get(route('admin.registrations.show', [
                'registration' => $registration,
                'period_id' => $this->historicalPeriod->id,
            ]))
            ->assertNotFound();
    }

    private function makePeriod(
        string $name,
        int $yearStart,
        int $yearEnd,
        bool $isActive,
        string $status
    ): PpdbPeriod {
        return PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => $name,
            'year_start' => $yearStart,
            'year_end' => $yearEnd,
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

    private function makeRegistration(
        PpdbPeriod $period
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::query()->create([
            'period_id' => $period->id,
            'admission_path_id' => $this->admissionPath->id,
            'major_id' => $this->major->id,

            'registration_number' =>
                'PERIOD-SHOW-'
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
            'full_name' => 'PERIOD SHOW TEST '.$sequence,
            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',
            'origin_school' => 'SMP PERIOD SHOW TEST',

            'whatsapp' =>
                '08128888'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'data_source' => 'ADMIN',
            'status' => 'REGISTERED',
            'created_by' => $this->admin->id,
            'registered_at' => now(),
            'notes' => null,
        ]);
    }
}