<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\School;
use App\Services\PeriodContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class AdminPeriodContextTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PpdbPeriod $activePeriod;

    private PpdbPeriod $historicalPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK MAARIF 9 KEBUMEN',
        ]);

        $this->historicalPeriod = PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'status' => 'CLOSED',
            'is_active' => false,
            'number_prefix' => 'MARSA',
            'number_year' => 2026,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 0,
        ]);

        $this->activePeriod = PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 0,
        ]);
    }

    public function test_it_uses_explicit_valid_period(): void
    {
        $request = Request::create(
            '/admin/test',
            'GET',
            ['period_id' => $this->historicalPeriod->id]
        );

        $period = app(PeriodContext::class)
            ->resolveAdminPeriod($request);

        $this->assertNotNull($period);
        $this->assertSame(
            $this->historicalPeriod->id,
            $period->id
        );
    }

    public function test_it_uses_active_period_when_period_id_is_absent(): void
    {
        $request = Request::create(
            '/admin/test',
            'GET'
        );

        $period = app(PeriodContext::class)
            ->resolveAdminPeriod($request);

        $this->assertNotNull($period);
        $this->assertSame(
            $this->activePeriod->id,
            $period->id
        );
    }

    public function test_invalid_explicit_period_does_not_fallback_to_active_period(): void
    {
        $request = Request::create(
            '/admin/test',
            'GET',
            ['period_id' => 999999]
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(PeriodContext::class)
            ->resolveAdminPeriod($request);
    }

    public function test_archived_explicit_period_is_not_selectable(): void
    {
        $archivedPeriod = PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'CLOSED',
            'is_active' => false,
            'number_prefix' => 'MARSA',
            'number_year' => 2025,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 0,
            'archived_at' => now(),
        ]);

        $request = Request::create(
            '/admin/test',
            'GET',
            ['period_id' => $archivedPeriod->id]
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(PeriodContext::class)
            ->resolveAdminPeriod($request);
    }

    public function test_it_returns_null_when_no_period_exists(): void
    {
        PpdbPeriod::query()->delete();

        $request = Request::create(
            '/admin/test',
            'GET'
        );

        $period = app(PeriodContext::class)
            ->resolveAdminPeriod($request);

        $this->assertNull($period);
    }
}