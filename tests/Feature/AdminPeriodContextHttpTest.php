<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPeriodContextHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PpdbPeriod $activePeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::query()->create([
            'name' => 'SMK PERIOD HTTP TEST',
        ]);

        $this->activePeriod = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 0,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);
    }

    public function test_invalid_period_returns_404_across_operational_pages(): void
    {
        $this->assertNotEmpty(
            $this->operationalRoutes()
        );

        foreach ($this->operationalRoutes() as $routeName) {
            $this->actingAs($this->admin)
                ->get(route($routeName, [
                    'period_id' => 999999,
                ]))
                ->assertNotFound();
        }
    }

    public function test_absent_period_uses_active_period_across_operational_pages(): void
    {
        $this->assertNotEmpty(
            $this->operationalRoutes()
        );

        foreach ($this->operationalRoutes() as $routeName) {
            $this->actingAs($this->admin)
                ->get(route($routeName))
                ->assertOk()
                ->assertViewHas(
                    'selectedPeriod',
                    fn ($period) =>
                        $period?->id === $this->activePeriod->id
                );
        }
    }

    private function operationalRoutes(): array
    {
        return [
            'admin.registrations.index',
            'admin.admissions.index',
            'admin.recaps.index',
            'admin.recaps.origin-schools.index',
            'admin.recaps.reenrollment-finance.index',
            'admin.recaps.referrals.index',
            'admin.analytics.index',
            'admin.reenrollments.index',
            'admin.reports.index',
            'admin.whatsapp-logs.index',
        ];
    }
}