<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\School;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RegistrationServicePeriodContextTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private RegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK REGISTRATION SERVICE PERIOD TEST',
            'npsn' => '15151515',
        ]);

        $this->service = app(RegistrationService::class);
    }

    public function test_closed_period_is_rejected_by_registration_service(): void
    {
        $period = $this->makePeriod(
            '2026/2027',
            2026,
            'CLOSED',
            true,
            null
        );

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(
            'Periode SPMB tidak aktif atau tidak dibuka.'
        );

        $this->service->create([
            'period_id' => $period->id,
            'major_id' => 999999,
        ]);
    }

    public function test_inactive_period_is_rejected_by_registration_service(): void
    {
        $period = $this->makePeriod(
            '2027/2028',
            2027,
            'OPEN',
            false,
            null
        );

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(
            'Periode SPMB tidak aktif atau tidak dibuka.'
        );

        $this->service->create([
            'period_id' => $period->id,
            'major_id' => 999999,
        ]);
    }

    public function test_archived_period_is_rejected_by_registration_service(): void
    {
        $period = $this->makePeriod(
            '2027/2028',
            2027,
            'OPEN',
            true,
            now()
        );

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(
            'Periode SPMB tidak aktif atau tidak dibuka.'
        );

        $this->service->create([
            'period_id' => $period->id,
            'major_id' => 999999,
        ]);
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