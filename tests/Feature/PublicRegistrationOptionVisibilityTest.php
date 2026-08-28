<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicRegistrationOptionVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_public_form_hides_all_relief_options_and_only_shows_active_special_programs_for_current_period(): void
    {
        /*
         * =========================================================
         * 1. Simulasikan tanggal yang berada di jalur KHUSUS.
         * =========================================================
         */
        Carbon::setTestNow(
            Carbon::parse(
                '2027-02-15 10:00:00',
                config('app.timezone')
            )
        );

        $now = now();

        /*
         * =========================================================
         * 2. School.
         * =========================================================
         */
        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'SMK TEST VISIBILITY',
            'npsn' => '88888888',
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
         * =========================================================
         * 3. Periode aktif.
         * =========================================================
         */
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

        /*
         * =========================================================
         * 4. Jalur aktif agar form /daftar benar-benar ditampilkan.
         * =========================================================
         */
        DB::table('admission_paths')->insert([
            'period_id' => $periodId,
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

        /*
         * =========================================================
         * 5. Jurusan aktif.
         * =========================================================
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

        DB::table('period_majors')->insert([
            'period_id' => $periodId,
            'major_id' => $majorId,
            'quota' => null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /*
         * =========================================================
         * 6. KERINGANAN
         *
         * Semua variasi sengaja dibuat untuk memastikan bahwa
         * Keringanan sama sekali tidak tersedia pada form PUBLIC,
         * terlepas dari status master maupun pivot periodenya.
         *
         * A = master aktif + periode aktif
         * B = master aktif + periode nonaktif
         * C = master nonaktif + periode aktif
         *
         * SEMUANYA => TIDAK boleh tampil di PUBLIC.
         * =========================================================
         */
        $reliefVisibleId = DB::table('relief_options')->insertGetId([
            'name' => 'TEST KERINGANAN TAMPIL',
            'slug' => 'test-keringanan-tampil',
            'description' => null,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $reliefPeriodDisabledId = DB::table('relief_options')->insertGetId([
            'name' => 'TEST KERINGANAN NONAKTIF PERIODE',
            'slug' => 'test-keringanan-nonaktif-periode',
            'description' => null,
            'is_active' => true,
            'sort_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $reliefMasterDisabledId = DB::table('relief_options')->insertGetId([
            'name' => 'TEST KERINGANAN NONAKTIF MASTER',
            'slug' => 'test-keringanan-nonaktif-master',
            'description' => null,
            'is_active' => false,
            'sort_order' => 3,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('period_relief_options')->insert([
            [
                'ppdb_period_id' => $periodId,
                'relief_option_id' => $reliefVisibleId,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ppdb_period_id' => $periodId,
                'relief_option_id' => $reliefPeriodDisabledId,
                'is_active' => false,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ppdb_period_id' => $periodId,
                'relief_option_id' => $reliefMasterDisabledId,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        /*
         * =========================================================
         * 7. PROGRAM KHUSUS
         *
         * A = master aktif + periode aktif
         *     => HARUS tampil
         *
         * B = master aktif + periode nonaktif
         *     => TIDAK boleh tampil
         *
         * C = master nonaktif + periode aktif
         *     => TIDAK boleh tampil
         * =========================================================
         */
        $programVisibleId = DB::table('special_programs')->insertGetId([
            'name' => 'TEST PROGRAM TAMPIL',
            'slug' => 'test-program-tampil',
            'description' => null,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $programPeriodDisabledId = DB::table('special_programs')->insertGetId([
            'name' => 'TEST PROGRAM NONAKTIF PERIODE',
            'slug' => 'test-program-nonaktif-periode',
            'description' => null,
            'is_active' => true,
            'sort_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $programMasterDisabledId = DB::table('special_programs')->insertGetId([
            'name' => 'TEST PROGRAM NONAKTIF MASTER',
            'slug' => 'test-program-nonaktif-master',
            'description' => null,
            'is_active' => false,
            'sort_order' => 3,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('period_special_programs')->insert([
            [
                'ppdb_period_id' => $periodId,
                'special_program_id' => $programVisibleId,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ppdb_period_id' => $periodId,
                'special_program_id' => $programPeriodDisabledId,
                'is_active' => false,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ppdb_period_id' => $periodId,
                'special_program_id' => $programMasterDisabledId,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        /*
         * =========================================================
         * 8. GET form publik.
         * =========================================================
         */
        $response = $this->get(
            route('registration.create')
        );

        /*
         * Form harus berhasil dibuka.
         */
        $response->assertOk();

        /*
         * Jalur harus aktif sehingga form benar-benar dirender.
         */
        $response->assertSee('Khusus');

        /*
         * =========================================================
         * 9. Keringanan visibility.
         *
         * Semua Keringanan merupakan data internal.
         * Tidak boleh ada satu pun pada form PUBLIC.
         * =========================================================
         */
        $response->assertDontSee(
            'TEST KERINGANAN TAMPIL'
        );

        $response->assertDontSee(
            'TEST KERINGANAN NONAKTIF PERIODE'
        );

        $response->assertDontSee(
            'TEST KERINGANAN NONAKTIF MASTER'
        );

        $response->assertDontSee(
            'name="relief_options[]"',
            false
        );

        /*
         * Referral juga merupakan data internal.
         */
        $response->assertDontSee(
            'name="referrer_name"',
            false
        );

        $response->assertDontSee(
            'name="referrer_source"',
            false
        );

        /*
         * =========================================================
         * 10. Program Khusus visibility.
         * =========================================================
         */

        // Master aktif + periode aktif => tampil.
        $response->assertSee(
            'TEST PROGRAM TAMPIL'
        );

        // Master aktif + periode nonaktif => tidak tampil.
        $response->assertDontSee(
            'TEST PROGRAM NONAKTIF PERIODE'
        );

        // Master nonaktif + periode aktif => tidak tampil.
        $response->assertDontSee(
            'TEST PROGRAM NONAKTIF MASTER'
        );

        /*
         * Field Program Khusus harus tetap tersedia di PUBLIC.
         */
        $response->assertSee(
            'name="special_programs[]"',
            false
        );
    }
}