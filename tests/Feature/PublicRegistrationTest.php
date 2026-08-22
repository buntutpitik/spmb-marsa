<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_public_registration_can_be_submitted_end_to_end(): void
    {
        /*
         * =========================================================
         * 1. Simulasikan tanggal jalur KHUSUS.
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
         * 2. Buat School khusus test.
         * =========================================================
         */
        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'SMK TEST SPMB MARSA',
            'npsn' => '99999999',
            'address' => 'Alamat Test',
            'village' => 'Test',
            'district' => 'Test',
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
         * 3. Buat periode 2027/2028.
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

            'notes' => 'Periode otomatis untuk feature test.',
            'archived_at' => null,

            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /*
         * =========================================================
         * 4. Buat jalur KHUSUS.
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
         * 5. Buat jalur UMUM.
         * =========================================================
         */
        DB::table('admission_paths')->insert([
            'period_id' => $periodId,
            'name' => 'Umum',
            'code' => 'UMUM',

            'start_date' => '2027-04-01',
            'end_date' => '2027-06-30',

            'is_active' => true,
            'sort_order' => 2,

            'description' => null,

            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /*
         * =========================================================
         * 6. Buat jurusan TKRO.
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

        /*
         * =========================================================
         * 7. Aktifkan TKRO pada periode.
         * =========================================================
         */
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
         * 8. Buat master Asal Sekolah aktif.
         * =========================================================
         */
        $originSchoolId = DB::table('origin_schools')
            ->insertGetId([
                'name' => 'SMP FEATURE TEST',
                'type' => 'SMP',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        /*
         * =========================================================
         * 9. Buat 2 master Keringanan.
         * =========================================================
         */
        $reliefYatimId = DB::table('relief_options')->insertGetId([
            'name' => 'Yatim',
            'slug' => 'test-yatim',
            'description' => null,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $reliefAlumniId = DB::table('relief_options')->insertGetId([
            'name' => 'Anak Alumni',
            'slug' => 'test-anak-alumni',
            'description' => null,
            'is_active' => true,
            'sort_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /*
         * =========================================================
         * 10. Hubungkan Keringanan ke periode.
         * =========================================================
         */
        DB::table('period_relief_options')->insert([
            [
                'ppdb_period_id' => $periodId,
                'relief_option_id' => $reliefYatimId,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ppdb_period_id' => $periodId,
                'relief_option_id' => $reliefAlumniId,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        /*
         * =========================================================
         * 11. Buat 2 Program Khusus.
         * =========================================================
         */
        $kkoId = DB::table('special_programs')->insertGetId([
            'name' => 'Kelas Khusus Olahraga (KKO)',
            'slug' => 'test-kko',
            'description' => null,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pondokId = DB::table('special_programs')->insertGetId([
            'name' => 'Pondok Pesantren',
            'slug' => 'test-pondok-pesantren',
            'description' => null,
            'is_active' => true,
            'sort_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /*
         * =========================================================
         * 12. Hubungkan Program Khusus ke periode.
         * =========================================================
         */
        DB::table('period_special_programs')->insert([
            [
                'ppdb_period_id' => $periodId,
                'special_program_id' => $kkoId,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ppdb_period_id' => $periodId,
                'special_program_id' => $pondokId,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        /*
         * =========================================================
         * 13. Pastikan periode test bisa dibaca model.
         * =========================================================
         */
        $period = PpdbPeriod::query()
            ->findOrFail($periodId);

        $this->assertSame(
            '2027/2028',
            $period->name
        );

        /*
         * =========================================================
         * 14. POST sungguhan ke /daftar.
         *
         * Flow yang diuji:
         *
         * Route
         * -> StorePublicRegistrationRequest
         * -> PublicRegistrationController
         * -> RegistrationService
         * -> AdmissionPathResolver
         * -> database
         * =========================================================
         */
        $response = $this->post(
            route('registration.store'),
            [
                'period_id' => $periodId,
                'major_id' => $majorId,

                'nik' => '3399999999999999',
                'nisn' => '9999999999',

                'full_name' => 'TEST FEATURE SPMB MARSA',

                'birth_place' => 'Kebumen',
                'birth_date' => '2009-02-15',

                'gender' => 'L',
                'religion' => 'Islam',

                /*
                 * Asal sekolah sekarang menggunakan master.
                 */
                'origin_school_id' => (string) $originSchoolId,
                'origin_school_other' => null,

                'hamlet' => 'Test',
                'rt' => '001',
                'rw' => '002',
                'village' => 'Desa Test',
                'district' => 'Kebumen',
                'city' => 'Kebumen',
                'province' => 'Jawa Tengah',
                'postal_code' => '54311',

                'father_name' => 'AYAH FEATURE TEST',
                'father_job' => 'Wiraswasta',

                'mother_name' => 'IBU FEATURE TEST',
                'mother_job' => 'Ibu Rumah Tangga',

                'whatsapp' => '081299999999',

                'graduation_score' => 90,

                'referrer_name' => 'FEATURE TEST',
                'referrer_source' => 'TEST',

                'relief_options' => [
                    $reliefYatimId,
                    $reliefAlumniId,
                ],

                'special_programs' => [
                    $kkoId,
                    $pondokId,
                ],

                'notes' => 'Automated feature test SPMB MARSA.',
            ]
        );

        /*
         * =========================================================
         * 15. Tidak boleh gagal validation.
         * =========================================================
         */
        $response->assertSessionHasNoErrors();

        /*
         * =========================================================
         * 16. Ambil registration hasil POST.
         * =========================================================
         */
        $registration = Registration::query()
            ->with([
                'period',
                'admissionPath',
                'major',
                'reliefOptions',
                'specialPrograms',
            ])
            ->where('period_id', $periodId)
            ->where('nik', '3399999999999999')
            ->firstOrFail();

        /*
         * =========================================================
         * 17. Validasi registration utama.
         * =========================================================
         */
        $this->assertSame(
            'REGISTERED',
            $registration->status
        );

        $this->assertSame(
            'PUBLIC',
            $registration->data_source
        );

        $this->assertSame(
            $periodId,
            $registration->period_id
        );

        $this->assertSame(
            $majorId,
            $registration->major_id
        );

        /*
         * Snapshot asal sekolah harus menggunakan nama dari master.
         */
        $this->assertSame(
            'SMP FEATURE TEST',
            $registration->origin_school
        );

        /*
         * Resolver harus memilih KHUSUS.
         */
        $this->assertSame(
            'KHUSUS',
            $registration->admissionPath->code
        );

        /*
         * Nomor pendaftaran harus terbentuk.
         */
        $this->assertNotNull(
            $registration->registration_number
        );

        $this->assertStringStartsWith(
            'MARSA-2027-TKRO-',
            $registration->registration_number
        );

        /*
         * =========================================================
         * 18. Public Token.
         * =========================================================
         */
        $this->assertNotNull(
            $registration->public_token
        );

        $this->assertSame(
            26,
            strlen($registration->public_token)
        );

        /*
         * =========================================================
         * 19. Redirect harus menggunakan public token.
         * =========================================================
         */
        $response->assertRedirect(
            route(
                'registration.success',
                [
                    'publicToken' => $registration->public_token,
                ]
            )
        );

        /*
         * =========================================================
         * 20. Validasi Keringanan.
         * =========================================================
         */
        $savedReliefIds = $registration
            ->reliefOptions
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $expectedReliefIds = collect([
            $reliefYatimId,
            $reliefAlumniId,
        ])
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            $expectedReliefIds,
            $savedReliefIds
        );

        /*
         * =========================================================
         * 21. Validasi Program Khusus.
         * =========================================================
         */
        $savedProgramIds = $registration
            ->specialPrograms
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $expectedProgramIds = collect([
            $kkoId,
            $pondokId,
        ])
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            $expectedProgramIds,
            $savedProgramIds
        );

        /*
         * =========================================================
         * 22. Pivot Keringanan harus benar.
         * =========================================================
         */
        $this->assertDatabaseHas(
            'registration_relief_options',
            [
                'registration_id' => $registration->id,
                'relief_option_id' => $reliefYatimId,
            ]
        );

        $this->assertDatabaseHas(
            'registration_relief_options',
            [
                'registration_id' => $registration->id,
                'relief_option_id' => $reliefAlumniId,
            ]
        );

        /*
         * =========================================================
         * 23. Pivot Program Khusus harus benar.
         * =========================================================
         */
        $this->assertDatabaseHas(
            'registration_special_programs',
            [
                'registration_id' => $registration->id,
                'special_program_id' => $kkoId,
            ]
        );

        $this->assertDatabaseHas(
            'registration_special_programs',
            [
                'registration_id' => $registration->id,
                'special_program_id' => $pondokId,
            ]
        );

        /*
         * =========================================================
         * 24. Status history awal.
         * =========================================================
         */
        $this->assertDatabaseHas(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'from_status' => null,
                'to_status' => 'REGISTERED',
            ]
        );

        /*
         * =========================================================
         * 25. Activity log.
         * =========================================================
         */
        $this->assertDatabaseHas(
            'activity_logs',
            [
                'registration_id' => $registration->id,
                'action' => 'CREATE_REGISTRATION',
            ]
        );

        /*
         * =========================================================
         * 26. Halaman success harus bisa dibuka.
         * =========================================================
         */
        $successResponse = $this->get(
            route(
                'registration.success',
                [
                    'publicToken' => $registration->public_token,
                ]
            )
        );

        $successResponse
            ->assertOk()
            ->assertSee('Pendaftaran Berhasil')
            ->assertSee(
                $registration->registration_number
            )
            ->assertSee('TEST FEATURE SPMB MARSA')
            ->assertSee('Khusus')
            ->assertSee('TKRO')
            ->assertSee('Yatim')
            ->assertSee('Anak Alumni')
            ->assertSee('Kelas Khusus Olahraga (KKO)')
            ->assertSee('Pondok Pesantren');
    }
}