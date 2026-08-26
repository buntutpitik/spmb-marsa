<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\TestCase;

class AdminPpdbPeriodTransactionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_update_rolls_back_target_and_other_active_period_when_activity_log_fails(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $school = School::query()->create([
            'name' => 'SMK TEST PERIOD TRANSACTION',
            'npsn' => '11223344',
            'city' => 'Kebumen',
            'province' => 'Jawa Tengah',
            'postal_code' => '54311',
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

        $targetPeriod = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'registration_open' => '2028-01-01',
            'registration_close' => '2028-06-30',
            'status' => 'DRAFT',
            'is_active' => false,
            'number_prefix' => 'MARSA',
            'number_year' => 2028,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 400000,
        ]);

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Forced activity log failure.'
            );
        });

        $this->actingAs($superadmin);
        $this->withoutExceptionHandling();

        try {
            $this->put(
                route(
                    'admin.periods.update',
                    $targetPeriod
                ),
                [
                    'name' => '2028/2029',
                    'year_start' => 2028,
                    'year_end' => 2029,
                    'registration_open' =>
                        '2028-01-01',
                    'registration_close' =>
                        '2028-06-30',
                    'status' => 'OPEN',
                    'is_active' => '1',
                    'principal_name' => null,
                    'principal_nip' => null,
                    'number_prefix' => 'MARSA',
                    'number_year' => 2028,
                    'number_digits' => 4,
                    'include_major_code' => '1',
                    'default_reenroll_fee' =>
                        400000,
                    'notes' => null,
                ]
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced activity log failure.',
                $exception->getMessage()
            );
        } finally {
            ActivityLog::flushEventListeners();
        }

        $activePeriod->refresh();
        $targetPeriod->refresh();

        $this->assertTrue(
            (bool) $activePeriod->is_active
        );

        $this->assertFalse(
            (bool) $targetPeriod->is_active
        );

        $this->assertSame(
            'DRAFT',
            $targetPeriod->status
        );

        $this->assertSame(
            1,
            PpdbPeriod::query()
                ->where('is_active', true)
                ->count()
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' => 'UPDATE_PPDB_PERIOD',
            ]
        );
    }
}
