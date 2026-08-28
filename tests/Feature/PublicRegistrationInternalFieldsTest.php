<?php

namespace Tests\Feature;

use App\Models\Registration;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PublicRegistrationInternalFieldsTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_public_form_does_not_show_internal_fields_but_keeps_special_program(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2027-02-15 10:00:00',
                config('app.timezone')
            )
        );

        $fixture = $this->makeFixture();

        $response = $this->get(
            route('registration.create')
        );

        $response->assertOk();

        /*
         * Pastikan form pendaftaran benar-benar dirender,
         * bukan halaman "pendaftaran belum dibuka".
         */
        $response->assertSee(
            'FORM INTERNAL FIELD TEST'
        );

        /*
         * Field yang hanya boleh dikelola petugas/admin
         * tidak boleh tersedia pada form publik.
         */
        $response->assertDontSee(
            'name="referrer_name"',
            false
        );

        $response->assertDontSee(
            'name="referrer_source"',
            false
        );

        $response->assertDontSee(
            'name="relief_options[]"',
            false
        );

        $response->assertDontSee(
            'KERINGANAN INTERNAL TEST'
        );

        /*
         * Program Khusus tetap menjadi pilihan siswa.
         */
        $response->assertSee(
            'name="special_programs[]"',
            false
        );

        $response->assertSee(
            'PROGRAM PUBLIK TEST'
        );

        $this->assertNotNull(
            $fixture['special_program_id']
        );
    }

    public function test_manipulated_public_request_cannot_store_internal_fields_but_can_store_special_program(): void
    {
        Queue::fake();

        Carbon::setTestNow(
            Carbon::parse(
                '2027-02-15 10:00:00',
                config('app.timezone')
            )
        );

        $fixture = $this->makeFixture();

        $response = $this->post(
            route('registration.store'),
            [
                'period_id' => $fixture['period_id'],
                'major_id' => $fixture['major_id'],

                'nik' => '3398888888888888',
                'nisn' => '9888888888',

                'full_name' => 'TEST MANIPULATED PUBLIC',

                'birth_place' => 'Kebumen',
                'birth_date' => '2009-02-15',
                'gender' => 'L',
                'religion' => 'Islam',

                'origin_school_id' =>
                    (string) $fixture['origin_school_id'],

                'hamlet' => 'Test',
                'rt' => '001',
                'rw' => '002',
                'village' => 'Desa Test',
                'district' => 'Kebumen',
                'city' => 'Kebumen',
                'province' => 'Jawa Tengah',
                'postal_code' => '54311',

                'father_name' => 'AYAH TEST',
                'father_job' => 'Wiraswasta',
                'mother_name' => 'IBU TEST',
                'mother_job' => 'Ibu Rumah Tangga',

                'whatsapp' => '081288888888',

                'graduation_score' => 90,

                /*
                 * Simulasi request yang dimanipulasi.
                 *
                 * Ketiga data ini harus diabaikan oleh
                 * endpoint PUBLIC.
                 */
                'referrer_name' =>
                    'MANIPULATED REFERRER',

                'referrer_source' =>
                    'MANIPULATED SOURCE',

                'relief_options' => [
                    $fixture['relief_option_id'],
                ],

                /*
                 * Program Khusus tetap sah dari PUBLIC.
                 */
                'special_programs' => [
                    $fixture['special_program_id'],
                ],

                'notes' => 'Manipulated public request test.',
            ]
        );

        $response->assertSessionHasNoErrors();

        $registration = Registration::query()
            ->with([
                'reliefOptions',
                'specialPrograms',
            ])
            ->where(
                'nik',
                '3398888888888888'
            )
            ->firstOrFail();

        /*
         * Referral dari request PUBLIC harus dibuang.
         */
        $this->assertNull(
            $registration->referrer_name
        );

        $this->assertNull(
            $registration->referrer_source
        );

        /*
         * Keringanan dari request PUBLIC harus dibuang.
         */
        $this->assertCount(
            0,
            $registration->reliefOptions
        );

        $this->assertDatabaseMissing(
            'registration_relief_options',
            [
                'registration_id' =>
                    $registration->id,

                'relief_option_id' =>
                    $fixture['relief_option_id'],
            ]
        );

        /*
         * Program Khusus tetap tersimpan.
         */
        $this->assertCount(
            1,
            $registration->specialPrograms
        );

        $this->assertDatabaseHas(
            'registration_special_programs',
            [
                'registration_id' =>
                    $registration->id,

                'special_program_id' =>
                    $fixture['special_program_id'],
            ]
        );
    }

    private function makeFixture(): array
    {
        $now = now();

        $schoolId = DB::table('schools')
            ->insertGetId([
                'name' => 'SMK INTERNAL FIELD TEST',
                'npsn' => '77777777',
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

        $periodId = DB::table('ppdb_periods')
            ->insertGetId([
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

        $majorId = DB::table('majors')
            ->insertGetId([
                'school_id' => $schoolId,
                'code' => 'TKRO',
                'name' => 'FORM INTERNAL FIELD TEST',
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

        $originSchoolId =
            DB::table('origin_schools')
                ->insertGetId([
                    'name' => 'SMP INTERNAL FIELD TEST',
                    'type' => 'SMP',
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

        /*
         * Keringanan sengaja dibuat aktif.
         * Walaupun aktif, tidak boleh muncul di PUBLIC.
         */
        $reliefOptionId =
            DB::table('relief_options')
                ->insertGetId([
                    'name' =>
                        'KERINGANAN INTERNAL TEST',

                    'slug' =>
                        'keringanan-internal-test',

                    'description' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

        DB::table('period_relief_options')
            ->insert([
                'ppdb_period_id' => $periodId,
                'relief_option_id' =>
                    $reliefOptionId,

                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        /*
         * Program Khusus tetap aktif dan harus
         * tersedia untuk siswa.
         */
        $specialProgramId =
            DB::table('special_programs')
                ->insertGetId([
                    'name' =>
                        'PROGRAM PUBLIK TEST',

                    'slug' =>
                        'program-publik-test',

                    'description' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

        DB::table('period_special_programs')
            ->insert([
                'ppdb_period_id' => $periodId,
                'special_program_id' =>
                    $specialProgramId,

                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        return [
            'period_id' => $periodId,
            'major_id' => $majorId,
            'origin_school_id' => $originSchoolId,
            'relief_option_id' => $reliefOptionId,
            'special_program_id' => $specialProgramId,
        ];
    }
}