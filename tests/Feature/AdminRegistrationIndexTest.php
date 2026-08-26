<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRegistrationIndexTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PpdbPeriod $activePeriod;

    private PpdbPeriod $historicalPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK REGISTRATION INDEX TEST',
        ]);

        $this->historicalPeriod = $this->makePeriod([
            'name' => '2026/2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'status' => 'CLOSED',
            'is_active' => false,
            'number_year' => 2026,
        ]);

        $this->activePeriod = $this->makePeriod([
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_year' => 2027,
        ]);
    }

    public function test_guest_cannot_access_registration_index(): void
    {
        $this->get(
            route('admin.registrations.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_explicit_historical_period_is_used(): void
    {
        $user = $this->makeUser('ADMIN');

        $this->actingAs($user)
            ->get(
                route('admin.registrations.index', [
                    'period_id' => $this->historicalPeriod->id,
                ])
            )
            ->assertOk()
            ->assertViewHas(
                'selectedPeriod',
                fn ($period) =>
                    $period?->id === $this->historicalPeriod->id
            );
    }

    public function test_invalid_explicit_period_returns_404(): void
    {
        $user = $this->makeUser('ADMIN');

        $this->actingAs($user)
            ->get(
                route('admin.registrations.index', [
                    'period_id' => 999999,
                ])
            )
            ->assertNotFound();
    }

    public function test_absent_period_id_uses_active_period(): void
    {
        $user = $this->makeUser('ADMIN');

        $this->actingAs($user)
            ->get(
                route('admin.registrations.index')
            )
            ->assertOk()
            ->assertViewHas(
                'selectedPeriod',
                fn ($period) =>
                    $period?->id === $this->activePeriod->id
            );
    }

    private function makePeriod(
        array $overrides = []
    ): PpdbPeriod {
        return PpdbPeriod::query()->create(
            array_merge([
                'school_id' => $this->school->id,
                'name' => '2027/2028',
                'year_start' => 2027,
                'year_end' => 2028,
                'registration_open' => '2027-01-01',
                'registration_close' => '2027-06-30',
                'status' => 'OPEN',
                'is_active' => false,
                'number_prefix' => 'MARSA',
                'number_year' => 2027,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 0,
            ], $overrides)
        );
    }

    private function makeUser(
        string $role
    ): User {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }
}