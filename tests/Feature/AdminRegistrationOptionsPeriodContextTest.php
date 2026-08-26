<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\ReliefOption;
use App\Models\SpecialProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminRegistrationOptionsPeriodContextTest extends TestCase
{
    use DatabaseTransactions;

    private User $superadmin;

    private PpdbPeriod $activePeriod;

    private PpdbPeriod $archivedPeriod;

    private ReliefOption $reliefOption;

    private SpecialProgram $specialProgram;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'SMK PERIOD CONTEXT OPTIONS TEST',
            'npsn' => '88888888',
            'city' => 'Kebumen',
            'province' => 'Jawa Tengah',
            'postal_code' => '54311',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->activePeriod = PpdbPeriod::query()->create([
            'school_id' => $schoolId,
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
            'default_reenroll_fee' => 250000,
            'archived_at' => null,
        ]);

        $this->archivedPeriod = PpdbPeriod::query()->create([
            'school_id' => $schoolId,
            'name' => '2026/2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'registration_open' => '2026-01-01',
            'registration_close' => '2026-06-30',
            'status' => 'CLOSED',
            'is_active' => false,
            'number_prefix' => 'MARSA',
            'number_year' => 2026,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
            'archived_at' => now(),
        ]);

        $this->reliefOption = ReliefOption::query()->create([
            'name' => 'RELIEF PERIOD CONTEXT TEST',
            'slug' => 'relief-period-context-test',
            'description' => null,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->specialProgram = SpecialProgram::query()->create([
            'name' => 'PROGRAM PERIOD CONTEXT TEST',
            'slug' => 'program-period-context-test',
            'description' => null,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->actingAs($this->superadmin);
    }

    public function test_relief_option_store_rejects_archived_period(): void
    {
        $this->post(
            route('admin.relief-options.store'),
            [
                'period_id' => $this->archivedPeriod->id,
                'name' => 'ARCHIVED RELIEF STORE',
                'description' => null,
                'sort_order' => 20,
            ]
        )->assertNotFound();

        $this->assertDatabaseMissing('relief_options', [
            'name' => 'ARCHIVED RELIEF STORE',
        ]);
    }

    public function test_relief_option_update_rejects_archived_period(): void
    {
        $this->put(
            route('admin.relief-options.update', [
                'reliefOption' => $this->reliefOption,
            ]),
            [
                'period_id' => $this->archivedPeriod->id,
                'name' => 'MUTATED RELIEF',
                'description' => null,
                'sort_order' => 30,
            ]
        )->assertNotFound();

        $this->assertDatabaseHas('relief_options', [
            'id' => $this->reliefOption->id,
            'name' => 'RELIEF PERIOD CONTEXT TEST',
        ]);
    }

    public function test_relief_option_period_toggle_rejects_archived_period(): void
    {
        $this->patch(
            route('admin.relief-options.toggle-period', [
                'reliefOption' => $this->reliefOption,
            ]),
            [
                'period_id' => $this->archivedPeriod->id,
            ]
        )->assertNotFound();

        $this->assertDatabaseMissing('period_relief_options', [
            'ppdb_period_id' => $this->archivedPeriod->id,
            'relief_option_id' => $this->reliefOption->id,
        ]);
    }

    public function test_relief_option_master_toggle_rejects_archived_period_context(): void
    {
        $this->patch(
            route('admin.relief-options.toggle-master', [
                'reliefOption' => $this->reliefOption,
            ]),
            [
                'period_id' => $this->archivedPeriod->id,
            ]
        )->assertNotFound();

        $this->assertDatabaseHas('relief_options', [
            'id' => $this->reliefOption->id,
            'is_active' => 1,
        ]);
    }

    public function test_special_program_store_rejects_archived_period(): void
    {
        $this->post(
            route('admin.special-programs.store'),
            [
                'period_id' => $this->archivedPeriod->id,
                'name' => 'ARCHIVED PROGRAM STORE',
                'description' => null,
                'sort_order' => 20,
            ]
        )->assertNotFound();

        $this->assertDatabaseMissing('special_programs', [
            'name' => 'ARCHIVED PROGRAM STORE',
        ]);
    }

    public function test_special_program_update_rejects_archived_period(): void
    {
        $this->put(
            route('admin.special-programs.update', [
                'specialProgram' => $this->specialProgram,
            ]),
            [
                'period_id' => $this->archivedPeriod->id,
                'name' => 'MUTATED PROGRAM',
                'description' => null,
                'sort_order' => 30,
            ]
        )->assertNotFound();

        $this->assertDatabaseHas('special_programs', [
            'id' => $this->specialProgram->id,
            'name' => 'PROGRAM PERIOD CONTEXT TEST',
        ]);
    }

    public function test_special_program_period_toggle_rejects_archived_period(): void
    {
        $this->patch(
            route('admin.special-programs.toggle-period', [
                'specialProgram' => $this->specialProgram,
            ]),
            [
                'period_id' => $this->archivedPeriod->id,
            ]
        )->assertNotFound();

        $this->assertDatabaseMissing('period_special_programs', [
            'ppdb_period_id' => $this->archivedPeriod->id,
            'special_program_id' => $this->specialProgram->id,
        ]);
    }

    public function test_special_program_master_toggle_rejects_archived_period_context(): void
    {
        $this->patch(
            route('admin.special-programs.toggle-master', [
                'specialProgram' => $this->specialProgram,
            ]),
            [
                'period_id' => $this->archivedPeriod->id,
            ]
        )->assertNotFound();

        $this->assertDatabaseHas('special_programs', [
            'id' => $this->specialProgram->id,
            'is_active' => 1,
        ]);
    }
}