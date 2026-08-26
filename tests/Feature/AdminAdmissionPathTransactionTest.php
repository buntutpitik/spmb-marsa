<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class AdminAdmissionPathTransactionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_rolls_back_admission_path_when_activity_log_fails(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $now = now();

        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'SMK TEST ADMISSION PATH TRANSACTION',
            'npsn' => '87654321',
            'address' => null,
            'village' => null,
            'district' => null,
            'city' => 'Kebumen',
            'province' => 'Jawa Tengah',
            'postal_code' => '54311',
            'phone' => null,
            'whatsapp' => null,
            'email' => null,
            'website' => null,
            'logo_path' => null,
            'favicon_path' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $periodId = DB::table('ppdb_periods')->insertGetId([
            'school_id' => $schoolId,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => true,
            'principal_name' => null,
            'principal_nip' => null,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
            'notes' => null,
            'archived_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Forced activity log failure.'
            );
        });

        $this->actingAs($superadmin);
        $this->withoutExceptionHandling();

        try {
            $this->post(
                route('admin.admission-paths.store'),
                [
                    'period_id' => $periodId,
                    'name' => 'ROLLBACK ADMISSION PATH',
                    'code' => 'ROLLBACK',
                    'start_date' => '2027-01-01',
                    'end_date' => '2027-03-31',
                    'sort_order' => 1,
                    'description' => null,
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

        $this->assertDatabaseMissing('admission_paths', [
            'period_id' => $periodId,
            'code' => 'ROLLBACK',
        ]);

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'CREATE_ADMISSION_PATH',
        ]);
    }
}