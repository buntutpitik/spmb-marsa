<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_only_uses_data_from_active_period(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2027-02-15 10:00:00',
                config('app.timezone')
            )
        );

        $now = now();

        /*
         * ---------------------------------------------------------
         * Login admin.
         * ---------------------------------------------------------
         */
        $admin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        /*
         * ---------------------------------------------------------
         * Sekolah.
         * ---------------------------------------------------------
         */
        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'SMK TEST DASHBOARD',
            'npsn' => '66666666',
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

        /*
         * ---------------------------------------------------------
         * Periode lama / tidak aktif.
         * ---------------------------------------------------------
         */
        $oldPeriodId = DB::table('ppdb_periods')->insertGetId([
            'school_id' => $schoolId,
            'name' => '2026/2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'registration_open' => '2026-01-01',
            'registration_close' => '2026-06-30',
            'status' => 'CLOSED',
            'is_active' => false,
            'principal_name' => null,
            'principal_nip' => null,
            'number_prefix' => 'MARSA',
            'number_year' => 2026,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
            'notes' => null,
            'archived_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /*
         * ---------------------------------------------------------
         * Periode aktif.
         * ---------------------------------------------------------
         */
        $activePeriodId = DB::table('ppdb_periods')->insertGetId([
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

        /*
         * ---------------------------------------------------------
         * Jurusan.
         * ---------------------------------------------------------
         */
        $majorId = DB::table('majors')->insertGetId([
            'school_id' => $schoolId,
            'code' => 'TKRO',
            'name' => 'Teknik Kendaraan Ringan Otomotif',
            'short_name' => 'TKRO',
            'description' => null,
            'icon_path' => null,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /*
         * ---------------------------------------------------------
         * Jalur.
         * ---------------------------------------------------------
         */
        $activePathId = DB::table('admission_paths')->insertGetId([
            'period_id' => $activePeriodId,
            'name' => 'Khusus',
            'code' => 'KHUSUS',
            'start_date' => '2027-01-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'sort_order' => 1,
            'description' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $oldPathId = DB::table('admission_paths')->insertGetId([
            'period_id' => $oldPeriodId,
            'name' => 'Umum',
            'code' => 'UMUM',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
            'sort_order' => 1,
            'description' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /*
         * ---------------------------------------------------------
         * Helper membuat registration.
         * ---------------------------------------------------------
         */
        $makeRegistration = function (
            int $periodId,
            int $pathId,
            string $number,
            string $nik,
            string $name,
            string $status,
            string $registeredAt
        ) use ($majorId): int {
            return DB::table('registrations')->insertGetId([
                'period_id' => $periodId,
                'admission_path_id' => $pathId,
                'major_id' => $majorId,

                'registration_number' => $number,
                'public_token' => null,

                'nik' => $nik,
                'nisn' => null,
                'full_name' => $name,

                'birth_place' => null,
                'birth_date' => null,
                'gender' => null,
                'religion' => null,
                'origin_school' => null,

                'hamlet' => null,
                'rt' => null,
                'rw' => null,
                'village' => null,
                'district' => null,
                'city' => null,
                'province' => null,
                'postal_code' => null,

                'father_name' => null,
                'mother_name' => null,
                'father_job' => null,
                'mother_job' => null,

                'whatsapp' => '081200000000',

                'graduation_score' => null,
                'achievement_relief' => null,

                'referrer_name' => null,
                'referrer_source' => null,

                'data_source' => 'PUBLIC',
                'status' => $status,
                'created_by' => null,

                'registered_at' => $registeredAt,

                'accepted_at' => $status === 'ACCEPTED'
                    ? $registeredAt
                    : null,

                'rejected_at' => $status === 'REJECTED'
                    ? $registeredAt
                    : null,

                'reenrolled_at' => $status === 'REENROLLED'
                    ? $registeredAt
                    : null,

                'withdrawn_at' => $status === 'WITHDRAWN'
                    ? $registeredAt
                    : null,

                'notes' => null,

                'created_at' => $registeredAt,
                'updated_at' => $registeredAt,
            ]);
        };

        /*
         * ---------------------------------------------------------
         * Data periode aktif.
         *
         * Masing-masing status dibuat 1.
         * Total aktif = 5.
         * ---------------------------------------------------------
         */
        $registeredId = $makeRegistration(
            $activePeriodId,
            $activePathId,
            'MARSA-2027-TKRO-0001',
            '3300000000000001',
            'SISWA REGISTERED',
            'REGISTERED',
            '2027-02-15 10:00:00'
        );

        $acceptedId = $makeRegistration(
            $activePeriodId,
            $activePathId,
            'MARSA-2027-TKRO-0002',
            '3300000000000002',
            'SISWA ACCEPTED',
            'ACCEPTED',
            '2027-02-14 10:00:00'
        );

        $rejectedId = $makeRegistration(
            $activePeriodId,
            $activePathId,
            'MARSA-2027-TKRO-0003',
            '3300000000000003',
            'SISWA REJECTED',
            'REJECTED',
            '2027-02-13 10:00:00'
        );

        $reenrolledId = $makeRegistration(
            $activePeriodId,
            $activePathId,
            'MARSA-2027-TKRO-0004',
            '3300000000000004',
            'SISWA REENROLLED',
            'REENROLLED',
            '2027-02-12 10:00:00'
        );

        $withdrawnId = $makeRegistration(
            $activePeriodId,
            $activePathId,
            'MARSA-2027-TKRO-0005',
            '3300000000000005',
            'SISWA WITHDRAWN',
            'WITHDRAWN',
            '2027-02-11 10:00:00'
        );

        /*
         * ---------------------------------------------------------
         * Data periode lama.
         *
         * Data ini TIDAK boleh masuk statistik dashboard.
         * ---------------------------------------------------------
         */
        $oldRegistrationId = $makeRegistration(
            $oldPeriodId,
            $oldPathId,
            'MARSA-2026-TKRO-9999',
            '3300000000009999',
            'SISWA PERIODE LAMA',
            'ACCEPTED',
            '2026-02-15 10:00:00'
        );

        /*
         * ---------------------------------------------------------
         * Activity periode aktif.
         * ---------------------------------------------------------
         */
        DB::table('activity_logs')->insert([
            'user_id' => null,
            'registration_id' => $registeredId,
            'action' => 'CREATE_REGISTRATION',
            'description' => 'Aktivitas periode aktif.',
            'metadata' => json_encode([
                'registration_number' => 'MARSA-2027-TKRO-0001',
            ]),
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => '2027-02-15 10:00:00',
        ]);

        /*
         * Activity periode lama.
         * Tidak boleh masuk latestActivities dashboard.
         */
        DB::table('activity_logs')->insert([
            'user_id' => null,
            'registration_id' => $oldRegistrationId,
            'action' => 'CREATE_REGISTRATION',
            'description' => 'Aktivitas periode lama.',
            'metadata' => json_encode([
                'registration_number' => 'MARSA-2026-TKRO-9999',
            ]),
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => '2027-02-16 10:00:00',
        ]);

        /*
         * ---------------------------------------------------------
         * GET Dashboard.
         * ---------------------------------------------------------
         */
        $response = $this->get(
            route('dashboard')
        );

        $response->assertOk();

        /*
         * ---------------------------------------------------------
         * Periode aktif.
         * ---------------------------------------------------------
         */
        $response->assertViewHas(
            'activePeriod',
            fn ($period) =>
                $period !== null
                && $period->id === $activePeriodId
                && $period->name === '2027/2028'
        );

        /*
         * ---------------------------------------------------------
         * Statistik.
         * ---------------------------------------------------------
         */
        $response->assertViewHas(
            'stats',
            function (array $stats): bool {
                return $stats['total'] === 5
                    && $stats['registered'] === 1
                    && $stats['accepted'] === 1
                    && $stats['rejected'] === 1
                    && $stats['reenrolled'] === 1
                    && $stats['withdrawn'] === 1;
            }
        );

        /*
         * ---------------------------------------------------------
         * Pendaftaran terbaru.
         *
         * Hanya periode aktif.
         * ---------------------------------------------------------
         */
        $response->assertViewHas(
            'latestRegistrations',
            function ($registrations): bool {
                return $registrations->count() === 5
                    && $registrations->first()->full_name
                        === 'SISWA REGISTERED'
                    && $registrations
                        ->pluck('full_name')
                        ->doesntContain('SISWA PERIODE LAMA');
            }
        );

        /*
         * ---------------------------------------------------------
         * Activity terbaru.
         *
         * Activity periode lama sengaja punya created_at lebih baru,
         * tetapi tetap tidak boleh ikut karena period_id berbeda.
         * ---------------------------------------------------------
         */
        $response->assertViewHas(
            'latestActivities',
            function ($activities): bool {
                return $activities->count() === 1
                    && $activities->first()->description
                        === 'Aktivitas periode aktif.';
            }
        );

        /*
         * ---------------------------------------------------------
         * Tampilan dashboard.
         * ---------------------------------------------------------
         */
        $response
            ->assertSee('Dashboard SPMB MARSA')
            ->assertSee('2027/2028')
            ->assertSee('SISWA REGISTERED')
            ->assertDontSee('SISWA PERIODE LAMA');

        /*
         * ---------------------------------------------------------
         * Sanity check agar ID fixture benar-benar terpakai.
         * ---------------------------------------------------------
         */
        $this->assertDatabaseHas('registrations', [
            'id' => $acceptedId,
            'status' => 'ACCEPTED',
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $rejectedId,
            'status' => 'REJECTED',
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $reenrolledId,
            'status' => 'REENROLLED',
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $withdrawnId,
            'status' => 'WITHDRAWN',
        ]);
    }
}