<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\AdmissionPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use RuntimeException;

class AdminPpdbPeriodCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_create_draft_period_without_changing_active_period(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PERIOD CREATION TEST',
            'npsn' => '87654321',
        ]);

        $activePeriod = PpdbPeriod::query()->create([
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
            'default_reenroll_fee' => 250000,
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)
            ->post(route('admin.periods.store'), [
                'school_id' => $school->id,
                'name' => '2028/2029',
                'year_start' => 2028,
                'year_end' => 2029,
                'registration_open' => '2028-01-01',
                'registration_close' => '2028-06-30',
                'number_prefix' => 'MARSA',
                'number_year' => 2028,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
                'copy_from_period_id' => null,
            ]);

        $response->assertRedirect(
            route('admin.periods.index')
        );

        $this->assertDatabaseHas('ppdb_periods', [
            'school_id' => $school->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'status' => 'DRAFT',
            'is_active' => false,
        ]);

        $activePeriod->refresh();

        $this->assertSame('OPEN', $activePeriod->status);
        $this->assertTrue($activePeriod->is_active);
    }

    public function test_cannot_create_duplicate_academic_year(): void
    {
        $school = School::query()->create([
            'name' => 'SMK DUPLICATE PERIOD TEST',
            'npsn' => '87654322',
        ]);

        PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'status' => 'DRAFT',
            'is_active' => false,
            'number_prefix' => 'MARSA',
            'number_year' => 2028,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)
            ->post(route('admin.periods.store'), [
                'name' => '2028/2029',
                'year_start' => 2028,
                'year_end' => 2029,
                'registration_open' => '2028-01-01',
                'registration_close' => '2028-06-30',
                'number_prefix' => 'MARSA',
                'number_year' => 2028,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
            ]);

        $response->assertSessionHasErrors('year_start');

        $this->assertSame(
            1,
            PpdbPeriod::query()
                ->where('school_id', $school->id)
                ->where('year_start', 2028)
                ->where('year_end', 2029)
                ->count()
        );
    }

    public function test_non_superadmin_cannot_create_period(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PERIOD AUTH TEST',
            'npsn' => '87654323',
        ]);

        foreach (['ADMIN', 'PANITIA', 'BENDAHARA'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($user)
                ->post(route('admin.periods.store'), [
                    'name' => '2028/2029',
                    'year_start' => 2028,
                    'year_end' => 2029,
                    'registration_open' => '2028-01-01',
                    'registration_close' => '2028-06-30',
                    'number_prefix' => 'MARSA',
                    'number_year' => 2028,
                    'number_digits' => 4,
                    'include_major_code' => true,
                    'default_reenroll_fee' => 250000,
                ])
                ->assertForbidden();
        }

        $this->assertSame(
            0,
            PpdbPeriod::query()
                ->where('school_id', $school->id)
                ->count()
        );
    }

    public function test_creating_period_records_activity_log(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PERIOD AUDIT TEST',
            'npsn' => '87654324',
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.50',
                'HTTP_USER_AGENT' => 'SPMB-Period-Creation-Test/1.0',
            ])
            ->post(route('admin.periods.store'), [
                'name' => '2028/2029',
                'year_start' => 2028,
                'year_end' => 2029,
                'registration_open' => '2028-01-01',
                'registration_close' => '2028-06-30',
                'number_prefix' => 'MARSA',
                'number_year' => 2028,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.periods.index'));

        $period = PpdbPeriod::query()
            ->where('school_id', $school->id)
            ->where('year_start', 2028)
            ->firstOrFail();

        $log = ActivityLog::query()
            ->where('action', 'CREATE_PPDB_PERIOD')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($superadmin->id, $log->user_id);
        $this->assertSame($period->id, $log->metadata['period_id']);
        $this->assertSame($school->id, $log->metadata['school_id']);
        $this->assertSame('DRAFT', $log->metadata['status']);
        $this->assertFalse($log->metadata['is_active']);
        $this->assertSame('203.0.113.50', $log->ip_address);
        $this->assertSame(
            'SPMB-Period-Creation-Test/1.0',
            $log->user_agent
        );
    }

    public function test_period_creation_rolls_back_when_activity_log_fails(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PERIOD ROLLBACK TEST',
            'npsn' => '87654325',
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $payload = [
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'registration_open' => '2028-01-01',
            'registration_close' => '2028-06-30',
            'number_prefix' => 'MARSA',
            'number_year' => 2028,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ];

        $initialCount = PpdbPeriod::query()->count();

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Forced creation activity log failure.'
            );
        });

        try {
            $this->actingAs($superadmin);
            $this->withoutExceptionHandling();

            $this->post(
                route('admin.periods.store'),
                $payload
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced creation activity log failure.',
                $exception->getMessage()
            );
        } finally {
            ActivityLog::flushEventListeners();
        }

        $this->assertSame(
            $initialCount,
            PpdbPeriod::query()->count()
        );

        $this->assertDatabaseMissing('ppdb_periods', [
            'school_id' => $school->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
        ]);

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'CREATE_PPDB_PERIOD',
        ]);
    }

    public function test_creating_period_can_copy_admission_paths(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PERIOD COPY TEST',
            'npsn' => '87654326',
        ]);

        $source = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        AdmissionPath::query()->create([
            'period_id' => $source->id,
            'name' => 'Jalur Khusus',
            'code' => 'KHUSUS',
            'start_date' => '2027-01-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'sort_order' => 1,
            'description' => 'Pendaftaran jalur khusus.',
        ]);

        AdmissionPath::query()->create([
            'period_id' => $source->id,
            'name' => 'Jalur Mutasi',
            'code' => 'MUTASI',
            'start_date' => '2027-07-01',
            'end_date' => '2027-08-31',
            'is_active' => false,
            'sort_order' => 2,
            'description' => 'Pendaftaran jalur mutasi.',
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.periods.store'), [
                'name' => '2028/2029',
                'year_start' => 2028,
                'year_end' => 2029,
                'registration_open' => '2028-01-01',
                'registration_close' => '2028-08-31',
                'number_prefix' => 'MARSA',
                'number_year' => 2028,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
                'copy_from_period_id' => $source->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.periods.index'));

        $target = PpdbPeriod::query()
            ->where('school_id', $school->id)
            ->where('year_start', 2028)
            ->firstOrFail();

        $this->assertSame('DRAFT', $target->status);
        $this->assertFalse($target->is_active);

        $this->assertSame(
            2,
            AdmissionPath::query()
                ->where('period_id', $target->id)
                ->count()
        );

        $this->assertDatabaseHas('admission_paths', [
            'period_id' => $target->id,
            'code' => 'KHUSUS',
            'start_date' => '2028-01-01',
            'end_date' => '2028-03-31',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('admission_paths', [
            'period_id' => $target->id,
            'code' => 'MUTASI',
            'start_date' => '2028-07-01',
            'end_date' => '2028-08-31',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->assertSame(
            2,
            AdmissionPath::query()
                ->where('period_id', $source->id)
                ->count()
        );
    }

    public function test_creating_period_can_copy_majors_and_quotas(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PERIOD MAJOR COPY TEST',
            'npsn' => '87654327',
        ]);

        $source = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        $majorA = \App\Models\Major::query()->create([
            'school_id' => $school->id,
            'name' => 'Akuntansi',
            'code' => 'AKL',
            'is_active' => true,
        ]);

        $majorB = \App\Models\Major::query()->create([
            'school_id' => $school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'is_active' => true,
        ]);

        $source->majors()->attach([
            $majorA->id => [
                'quota' => 50,
                'is_active' => true,
            ],
            $majorB->id => [
                'quota' => null,
                'is_active' => false,
            ],
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.periods.store'), [
                'name' => '2028/2029',
                'year_start' => 2028,
                'year_end' => 2029,
                'registration_open' => '2028-01-01',
                'registration_close' => '2028-08-31',
                'number_prefix' => 'MARSA',
                'number_year' => 2028,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
                'copy_from_period_id' => $source->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.periods.index'));

        $target = PpdbPeriod::query()
            ->where('school_id', $school->id)
            ->where('year_start', 2028)
            ->firstOrFail();

        $this->assertSame('DRAFT', $target->status);
        $this->assertFalse($target->is_active);

        $this->assertDatabaseHas('period_majors', [
            'period_id' => $target->id,
            'major_id' => $majorA->id,
            'quota' => 50,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('period_majors', [
            'period_id' => $target->id,
            'major_id' => $majorB->id,
            'quota' => null,
            'is_active' => false,
        ]);

        $this->assertSame(
            2,
            \Illuminate\Support\Facades\DB::table('period_majors')
                ->where('period_id', $target->id)
                ->count()
        );

        $this->assertSame(
            2,
            \Illuminate\Support\Facades\DB::table('period_majors')
                ->where('period_id', $source->id)
                ->count()
        );
    }

    public function test_creating_period_can_copy_relief_options(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PERIOD RELIEF COPY TEST',
            'npsn' => '87654328',
        ]);

        $source = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        $reliefA = \App\Models\ReliefOption::query()->create([
            'school_id' => $school->id,
            'name' => 'Keringanan Prestasi',
            'slug' => 'keringanan-prestasi',
            'is_active' => true,
        ]);

        $reliefB = \App\Models\ReliefOption::query()->create([
            'school_id' => $school->id,
            'name' => 'Keringanan Sosial',
            'slug' => 'keringanan-sosial',
            'is_active' => true,
        ]);

        $source->reliefOptions()->attach([
            $reliefA->id => [
                'is_active' => true,
                'sort_order' => 3,
            ],
            $reliefB->id => [
                'is_active' => false,
                'sort_order' => 7,
            ],
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.periods.store'), [
                'name' => '2028/2029',
                'year_start' => 2028,
                'year_end' => 2029,
                'registration_open' => '2028-01-01',
                'registration_close' => '2028-08-31',
                'number_prefix' => 'MARSA',
                'number_year' => 2028,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
                'copy_from_period_id' => $source->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.periods.index'));

        $target = PpdbPeriod::query()
            ->where('school_id', $school->id)
            ->where('year_start', 2028)
            ->firstOrFail();

        $this->assertSame('DRAFT', $target->status);
        $this->assertFalse($target->is_active);

        $this->assertDatabaseHas('period_relief_options', [
            'ppdb_period_id' => $target->id,
            'relief_option_id' => $reliefA->id,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->assertDatabaseHas('period_relief_options', [
            'ppdb_period_id' => $target->id,
            'relief_option_id' => $reliefB->id,
            'is_active' => false,
            'sort_order' => 7,
        ]);

        $this->assertSame(
            2,
            \Illuminate\Support\Facades\DB::table('period_relief_options')
                ->where('ppdb_period_id', $target->id)
                ->count()
        );

        $this->assertSame(
            2,
            \Illuminate\Support\Facades\DB::table('period_relief_options')
                ->where('ppdb_period_id', $source->id)
                ->count()
        );
    }

    public function test_creating_period_can_copy_special_programs(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PERIOD SPECIAL PROGRAM COPY TEST',
            'npsn' => '87654329',
        ]);

        $source = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        $programA = \App\Models\SpecialProgram::query()->create([
            'name' => 'Program Tahfidz',
            'slug' => 'program-tahfidz',
            'description' => 'Program khusus Tahfidz.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $programB = \App\Models\SpecialProgram::query()->create([
            'name' => 'Program Bahasa',
            'slug' => 'program-bahasa',
            'description' => 'Program khusus Bahasa.',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $source->specialPrograms()->attach([
            $programA->id => [
                'is_active' => true,
                'sort_order' => 3,
            ],
            $programB->id => [
                'is_active' => false,
                'sort_order' => 7,
            ],
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.periods.store'), [
                'name' => '2028/2029',
                'year_start' => 2028,
                'year_end' => 2029,
                'registration_open' => '2028-01-01',
                'registration_close' => '2028-08-31',
                'number_prefix' => 'MARSA',
                'number_year' => 2028,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
                'copy_from_period_id' => $source->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.periods.index'));

        $target = PpdbPeriod::query()
            ->where('school_id', $school->id)
            ->where('year_start', 2028)
            ->firstOrFail();

        $this->assertSame('DRAFT', $target->status);
        $this->assertFalse($target->is_active);

        $this->assertDatabaseHas('period_special_programs', [
            'ppdb_period_id' => $target->id,
            'special_program_id' => $programA->id,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->assertDatabaseHas('period_special_programs', [
            'ppdb_period_id' => $target->id,
            'special_program_id' => $programB->id,
            'is_active' => false,
            'sort_order' => 7,
        ]);

        $this->assertSame(
            2,
            \Illuminate\Support\Facades\DB::table('period_special_programs')
                ->where('ppdb_period_id', $target->id)
                ->count()
        );

        $this->assertSame(
            2,
            \Illuminate\Support\Facades\DB::table('period_special_programs')
                ->where('ppdb_period_id', $source->id)
                ->count()
        );
    }

    public function test_copy_configuration_rolls_back_when_path_copy_fails(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PERIOD COPY ROLLBACK TEST',
            'npsn' => '87654330',
        ]);

        $source = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        AdmissionPath::query()->create([
            'period_id' => $source->id,
            'name' => 'Jalur Khusus',
            'code' => 'KHUSUS',
            'start_date' => '2027-01-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $initialPeriodCount = PpdbPeriod::query()->count();
        $initialPathCount = AdmissionPath::query()->count();

        AdmissionPath::created(function (AdmissionPath $path) use ($source) {
            if ($path->period_id !== $source->id) {
                throw new \RuntimeException(
                    'Forced configuration copy failure.'
                );
            }
        });

        try {
            $this->actingAs($superadmin);
            $this->withoutExceptionHandling();

            $this->post(route('admin.periods.store'), [
                'name' => '2028/2029',
                'year_start' => 2028,
                'year_end' => 2029,
                'registration_open' => '2028-01-01',
                'registration_close' => '2028-08-31',
                'number_prefix' => 'MARSA',
                'number_year' => 2028,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
                'copy_from_period_id' => $source->id,
            ]);

            $this->fail(
                'Expected configuration copy failure was not thrown.'
            );
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Forced configuration copy failure.',
                $exception->getMessage()
            );
        } finally {
            AdmissionPath::flushEventListeners();
        }

        $this->assertSame(
            $initialPeriodCount,
            PpdbPeriod::query()->count()
        );

        $this->assertSame(
            $initialPathCount,
            AdmissionPath::query()->count()
        );

        $this->assertDatabaseMissing('ppdb_periods', [
            'school_id' => $school->id,
            'year_start' => 2028,
            'year_end' => 2029,
        ]);

        $this->assertDatabaseHas('admission_paths', [
            'period_id' => $source->id,
            'code' => 'KHUSUS',
        ]);
    }

    public function test_cannot_copy_configuration_from_archived_period(): void
    {
        $school = School::query()->create([
            'name' => 'SMK ARCHIVED PERIOD COPY TEST',
            'npsn' => '87654331',
        ]);

        $source = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'CLOSED',
            'is_active' => false,
            'archived_at' => now(),
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $initialPeriodCount = PpdbPeriod::query()->count();

        $this->actingAs($superadmin)
            ->post(route('admin.periods.store'), [
                'name' => '2028/2029',
                'year_start' => 2028,
                'year_end' => 2029,
                'registration_open' => '2028-01-01',
                'registration_close' => '2028-08-31',
                'number_prefix' => 'MARSA',
                'number_year' => 2028,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
                'copy_from_period_id' => $source->id,
            ])
            ->assertSessionHasErrors('copy_from_period_id');

        $this->assertSame(
            $initialPeriodCount,
            PpdbPeriod::query()->count()
        );

        $this->assertDatabaseMissing('ppdb_periods', [
            'school_id' => $school->id,
            'year_start' => 2028,
            'year_end' => 2029,
        ]);
    }

    public function test_cannot_copy_leap_day_into_non_leap_year(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PERIOD LEAP YEAR TEST',
            'npsn' => '87654332',
        ]);

        $source = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2028,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        AdmissionPath::query()->create([
            'period_id' => $source->id,
            'name' => 'Jalur Khusus',
            'code' => 'KHUSUS',
            'start_date' => '2028-02-29',
            'end_date' => '2028-03-31',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $initialPeriodCount = PpdbPeriod::query()->count();
        $initialPathCount = AdmissionPath::query()->count();

        $this->actingAs($superadmin)
            ->post(route('admin.periods.store'), [
                'name' => '2029/2030',
                'year_start' => 2029,
                'year_end' => 2030,
                'registration_open' => '2029-01-01',
                'registration_close' => '2029-08-31',
                'number_prefix' => 'MARSA',
                'number_year' => 2029,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
                'copy_from_period_id' => $source->id,
            ])
            ->assertSessionHasErrors('copy_from_period_id');

        $this->assertSame(
            $initialPeriodCount,
            PpdbPeriod::query()->count()
        );

        $this->assertSame(
            $initialPathCount,
            AdmissionPath::query()->count()
        );

        $this->assertDatabaseMissing('ppdb_periods', [
            'school_id' => $school->id,
            'year_start' => 2029,
            'year_end' => 2030,
        ]);

        $this->assertDatabaseHas('admission_paths', [
            'period_id' => $source->id,
            'code' => 'KHUSUS',
            'start_date' => '2028-02-29',
        ]);
    }

    public function test_cannot_copy_configuration_from_another_school(): void
    {
        $targetSchool = School::query()->create([
            'name' => 'SMK PERIOD TARGET SCHOOL',
            'npsn' => '87654333',
        ]);

        $otherSchool = School::query()->create([
            'name' => 'SMK PERIOD OTHER SCHOOL',
            'npsn' => '87654334',
        ]);

        $foreignPeriod = PpdbPeriod::query()->create([
            'school_id' => $otherSchool->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'OTHER',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $initialPeriodCount = PpdbPeriod::query()->count();

        $this->actingAs($superadmin)
            ->post(route('admin.periods.store'), [
                'name' => '2028/2029',
                'year_start' => 2028,
                'year_end' => 2029,
                'registration_open' => '2028-01-01',
                'registration_close' => '2028-08-31',
                'number_prefix' => 'MARSA',
                'number_year' => 2028,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
                'copy_from_period_id' => $foreignPeriod->id,
            ])
            ->assertSessionHasErrors('copy_from_period_id');

        $this->assertSame(
            $initialPeriodCount,
            PpdbPeriod::query()->count()
        );

        $this->assertDatabaseMissing('ppdb_periods', [
            'school_id' => $targetSchool->id,
            'year_start' => 2028,
            'year_end' => 2029,
        ]);

        $this->assertDatabaseHas('ppdb_periods', [
            'id' => $foreignPeriod->id,
            'school_id' => $otherSchool->id,
            'year_start' => 2027,
        ]);
    }
}
