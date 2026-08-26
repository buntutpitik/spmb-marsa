<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class AdminMajorTransactionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_rolls_back_major_and_period_major_when_activity_log_fails(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $now = now();

        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'SMK TEST MAJOR TRANSACTION',
            'npsn' => '87654322',
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
                route('admin.majors.store'),
                [
                    'period_id' => $periodId,
                    'school_id' => $schoolId,
                    'code' => 'ROLLBACK',
                    'name' => 'ROLLBACK MAJOR',
                    'short_name' => 'ROLLBACK',
                    'description' => null,
                    'sort_order' => 99,
                    'quota' => 72,
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

        $this->assertDatabaseMissing('majors', [
            'school_id' => $schoolId,
            'code' => 'ROLLBACK',
        ]);

        $majorId = DB::table('majors')
            ->where('school_id', $schoolId)
            ->where('code', 'ROLLBACK')
            ->value('id');

        $this->assertNull($majorId);

        $this->assertDatabaseMissing('period_majors', [
            'period_id' => $periodId,
        ]);

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'CREATE_MAJOR',
        ]);
    }
}