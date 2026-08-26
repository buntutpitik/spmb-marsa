<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\School;
use App\Services\PeriodContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivePeriodContextTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK ACTIVE PERIOD CONTEXT TEST',
            'npsn' => '14141414',
        ]);
    }

    public function test_it_returns_active_open_non_archived_period(): void
    {
        $period = $this->makePeriod(
            '2027/2028',
            2027,
            'OPEN',
            true,
            null
        );

        $resolved = app(PeriodContext::class)
            ->resolveActivePeriod();

        $this->assertNotNull($resolved);

        $this->assertSame(
            $period->id,
            $resolved->id
        );
    }

    public function test_closed_period_is_not_active_public_period(): void
    {
        $this->makePeriod(
            '2027/2028',
            2027,
            'CLOSED',
            true,
            null
        );

        $this->assertNull(
            app(PeriodContext::class)
                ->resolveActivePeriod()
        );
    }

    public function test_inactive_period_is_not_active_public_period(): void
    {
        $this->makePeriod(
            '2027/2028',
            2027,
            'OPEN',
            false,
            null
        );

        $this->assertNull(
            app(PeriodContext::class)
                ->resolveActivePeriod()
        );
    }

    public function test_archived_period_is_not_active_public_period(): void
    {
        $this->makePeriod(
            '2027/2028',
            2027,
            'OPEN',
            true,
            now()
        );

        $this->assertNull(
            app(PeriodContext::class)
                ->resolveActivePeriod()
        );
    }

    public function test_explicit_active_period_id_can_be_resolved(): void
    {
        $period = $this->makePeriod(
            '2027/2028',
            2027,
            'OPEN',
            true,
            null
        );

        $resolved = app(PeriodContext::class)
            ->resolveActivePeriod(
                $period->id
            );

        $this->assertNotNull($resolved);

        $this->assertSame(
            $period->id,
            $resolved->id
        );
    }

    public function test_explicit_non_active_period_id_returns_null(): void
    {
        $period = $this->makePeriod(
            '2026/2027',
            2026,
            'CLOSED',
            false,
            null
        );

        $this->assertNull(
            app(PeriodContext::class)
                ->resolveActivePeriod(
                    $period->id
                )
        );
    }

    public function test_latest_active_period_is_selected_deterministically(): void
    {
        $this->makePeriod(
            '2027/2028',
            2027,
            'OPEN',
            true,
            null
        );

        $latest = $this->makePeriod(
            '2028/2029',
            2028,
            'OPEN',
            true,
            null
        );

        $resolved = app(PeriodContext::class)
            ->resolveActivePeriod();

        $this->assertNotNull($resolved);

        $this->assertSame(
            $latest->id,
            $resolved->id
        );
    }

    private function makePeriod(
        string $name,
        int $yearStart,
        string $status,
        bool $isActive,
        mixed $archivedAt
    ): PpdbPeriod {
        return PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => $name,
            'year_start' => $yearStart,
            'year_end' => $yearStart + 1,
            'registration_open' =>
                "{$yearStart}-01-01",
            'registration_close' =>
                "{$yearStart}-06-30",
            'status' => $status,
            'is_active' => $isActive,
            'number_prefix' => 'MARSA',
            'number_year' => $yearStart,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 350000,
            'archived_at' => $archivedAt,
        ]);
    }
}