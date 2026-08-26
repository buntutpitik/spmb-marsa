<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PeriodMajor;
use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMasterPeriodContextTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private School $school;

    private PpdbPeriod $period;

    private PpdbPeriod $archivedPeriod;

    private AdmissionPath $admissionPath;

    private Major $major;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'name' => 'SMK MASTER PERIOD CONTEXT TEST',
            'npsn' => '13131313',
        ]);

        $this->period = $this->makePeriod(
            '2027/2028',
            2027,
            2028,
            true,
            null
        );

        $this->archivedPeriod = $this->makePeriod(
            '2026/2027',
            2026,
            2027,
            false,
            now()
        );

        $this->admissionPath = AdmissionPath::query()->create([
            'period_id' => $this->archivedPeriod->id,
            'name' => 'Archived Path',
            'code' => 'ARCHIVED',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
            'sort_order' => 1,
            'description' => null,
        ]);

        $this->major = Major::query()->create([
            'school_id' => $this->school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
            'short_name' => 'RPL',
            'description' => null,
            'icon_path' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PeriodMajor::query()->create([
            'period_id' => $this->archivedPeriod->id,
            'major_id' => $this->major->id,
            'quota' => 72,
            'is_active' => true,
        ]);
    }

    public function test_admission_path_store_rejects_archived_period(): void
    {
        $this->actingAs($this->superadmin)
            ->post(
                route('admin.admission-paths.store'),
                [
                    'period_id' => $this->archivedPeriod->id,
                    'name' => 'Archived Store',
                    'code' => 'ARCSTORE',
                    'start_date' => '2026-04-01',
                    'end_date' => '2026-04-30',
                    'sort_order' => 2,
                    'description' => null,
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseMissing('admission_paths', [
            'period_id' => $this->archivedPeriod->id,
            'code' => 'ARCSTORE',
        ]);
    }

    public function test_admission_path_update_rejects_archived_period(): void
    {
        $this->actingAs($this->superadmin)
            ->put(
                route(
                    'admin.admission-paths.update',
                    $this->admissionPath
                ),
                [
                    'period_id' => $this->archivedPeriod->id,
                    'name' => 'Mutated Archived Path',
                    'code' => 'ARCHIVED',
                    'start_date' => '2026-01-01',
                    'end_date' => '2026-03-31',
                    'sort_order' => 1,
                    'description' => null,
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseHas('admission_paths', [
            'id' => $this->admissionPath->id,
            'name' => 'Archived Path',
        ]);
    }

    public function test_admission_path_toggle_rejects_archived_period(): void
    {
        $this->actingAs($this->superadmin)
            ->patch(
                route(
                    'admin.admission-paths.toggle',
                    $this->admissionPath
                ),
                [
                    'period_id' => $this->archivedPeriod->id,
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseHas('admission_paths', [
            'id' => $this->admissionPath->id,
            'is_active' => 1,
        ]);
    }

    public function test_major_store_rejects_archived_period(): void
    {
        $this->actingAs($this->superadmin)
            ->post(
                route('admin.majors.store'),
                [
                    'school_id' => $this->school->id,
                    'period_id' => $this->archivedPeriod->id,
                    'code' => 'TKRO',
                    'name' => 'Teknik Kendaraan Ringan Otomotif',
                    'short_name' => 'TKRO',
                    'description' => null,
                    'sort_order' => 2,
                    'quota' => 72,
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseMissing('majors', [
            'school_id' => $this->school->id,
            'code' => 'TKRO',
        ]);
    }

    public function test_major_update_rejects_archived_period(): void
    {
        $this->actingAs($this->superadmin)
            ->put(
                route(
                    'admin.majors.update',
                    $this->major
                ),
                [
                    'school_id' => $this->school->id,
                    'period_id' => $this->archivedPeriod->id,
                    'code' => 'RPL',
                    'name' => 'Mutated Major',
                    'short_name' => 'RPL',
                    'description' => null,
                    'sort_order' => 1,
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseHas('majors', [
            'id' => $this->major->id,
            'name' => 'Rekayasa Perangkat Lunak',
        ]);
    }

    public function test_major_master_toggle_rejects_archived_period(): void
    {
        $this->actingAs($this->superadmin)
            ->patch(
                route(
                    'admin.majors.toggle-master',
                    $this->major
                ),
                [
                    'period_id' => $this->archivedPeriod->id,
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseHas('majors', [
            'id' => $this->major->id,
            'is_active' => 1,
        ]);
    }

    public function test_major_period_update_rejects_archived_period(): void
    {
        $this->actingAs($this->superadmin)
            ->put(
                route(
                    'admin.majors.update-period',
                    $this->major
                ),
                [
                    'period_id' => $this->archivedPeriod->id,
                    'quota' => 99,
                    'is_active' => 1,
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseHas('period_majors', [
            'period_id' => $this->archivedPeriod->id,
            'major_id' => $this->major->id,
            'quota' => 72,
            'is_active' => 1,
        ]);
    }

    private function makePeriod(
        string $name,
        int $yearStart,
        int $yearEnd,
        bool $isActive,
        mixed $archivedAt
    ): PpdbPeriod {
        return PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => $name,
            'year_start' => $yearStart,
            'year_end' => $yearEnd,
            'registration_open' => "{$yearStart}-01-01",
            'registration_close' => "{$yearStart}-06-30",
            'status' => $isActive ? 'OPEN' : 'CLOSED',
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