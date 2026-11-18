<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminRegistrationCreateTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PpdbPeriod $activePeriod;

    private PpdbPeriod $historicalPeriod;

    private Major $major;

    private AdmissionPath $specialPath;

    private int $originSchoolId;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Tanggal sengaja berada SEBELUM pendaftaran publik dibuka.
         *
         * Ini kontrak penting:
         * input ADMIN tidak boleh bergantung pada tanggal publik.
         */
        Carbon::setTestNow(
            Carbon::parse(
                '2026-08-28 10:00:00',
                config('app.timezone')
            )
        );

        $this->school = School::query()->create([
            'name' => 'SMK ADMIN REGISTRATION CREATE TEST',
            'npsn' => '12345671',
        ]);

        $this->activePeriod = $this->makePeriod(
            '2027/2028',
            2027,
            true,
            'OPEN'
        );

        $this->historicalPeriod = $this->makePeriod(
            '2026/2027',
            2026,
            false,
            'CLOSED'
        );

        $this->major = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'is_active' => true,
        ]);

        $this->activePeriod->majors()->attach(
            $this->major->id,
            [
                'is_active' => true,
            ]
        );

        /*
         * Jalur ini baru berlaku mulai Januari 2027.
         *
         * Test berjalan pada 28 Agustus 2026.
         * ADMIN tetap harus dapat membuat pendaftaran.
         * Sistem otomatis menggunakan jalur aktif terdekat
         * yang akan datang.
         */
        $this->specialPath = AdmissionPath::query()->create([
            'period_id' => $this->activePeriod->id,
            'name' => 'Khusus',
            'code' => 'KHUSUS',
            'start_date' => '2027-01-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->originSchoolId = DB::table('origin_schools')
            ->insertGetId([
                'name' => 'SMP ADMIN CREATE TEST',
                'type' => 'SMP',
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        \App\Models\ReferralSource::query()->create([
            'name' => 'ALUMNI',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_all_operational_roles_can_open_create_form(): void
    {
        foreach (
            ['SUPERADMIN', 'ADMIN', 'PANITIA', 'BENDAHARA']
            as $role
        ) {
            $user = $this->makeUser($role);

            $this->actingAs($user)
                ->get(route(
                    'admin.registrations.create',
                    [
                        'period_id' => $this->activePeriod->id,
                    ]
                ))
                ->assertOk()
                ->assertSee('Tambah Pendaftar');
        }
    }

    public function test_create_form_does_not_expose_manual_admission_path_input(): void
    {
        $admin = $this->makeUser('ADMIN');

        $this->actingAs($admin)
            ->get(route(
                'admin.registrations.create',
                [
                    'period_id' => $this->activePeriod->id,
                ]
            ))
            ->assertOk()
            ->assertDontSee('name="admission_path_id"', false)
            ->assertSee(
                'Jalur pendaftaran ditentukan otomatis oleh sistem berdasarkan jadwal.'
            );
    }

    public function test_historical_period_cannot_open_create_form(): void
    {
        $admin = $this->makeUser('ADMIN');

        $this->actingAs($admin)
            ->get(route(
                'admin.registrations.create',
                [
                    'period_id' => $this->historicalPeriod->id,
                ]
            ))
            ->assertNotFound();
    }

    public function test_admin_can_create_registration_outside_public_registration_date(): void
    {
        $admin = $this->makeUser('ADMIN');

        $response = $this
            ->actingAs($admin)
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.50',
            ])
            ->withHeader(
                'User-Agent',
                'SPMB-MARSA-Admin-Create-Test/1.0'
            )
            ->post(
                route('admin.registrations.store'),
                $this->validPayload()
            );

        $response->assertSessionHasNoErrors();

        $registration = DB::table('registrations')
            ->where('period_id', $this->activePeriod->id)
            ->where('nik', '3377777777000001')
            ->first();

        $this->assertNotNull($registration);

        /*
         * Nilai sistem harus ditentukan server.
         */
        $this->assertSame(
            'ADMIN',
            $registration->data_source
        );

        $this->assertSame(
            'REGISTERED',
            $registration->status
        );

        $this->assertSame(
            $admin->id,
            $registration->created_by
        );

        /*
         * Jalur harus ditentukan otomatis oleh sistem.
         * Karena tanggal test masih sebelum pembukaan jalur,
         * ADMIN menggunakan jalur aktif terdekat yang akan datang.
         */
        $this->assertSame(
            $this->specialPath->id,
            $registration->admission_path_id
        );

        $this->assertSame(
            $this->major->id,
            $registration->major_id
        );

        /*
         * Nomor dan token tetap dibuat sistem.
         */
        $this->assertNotNull(
            $registration->registration_number
        );

        $this->assertStringStartsWith(
            'MARSA-2027-RPL-',
            $registration->registration_number
        );

        $this->assertNotNull(
            $registration->public_token
        );

        $this->assertSame(
            26,
            strlen($registration->public_token)
        );

        /*
         * Referral ADMIN harus tersimpan.
         */
        $this->assertSame(
            'PAK AHMAD',
            $registration->referrer_name
        );

        $this->assertSame(
            'ALUMNI',
            $registration->referrer_source
        );

        /*
         * Status history awal.
         */
        $this->assertDatabaseHas(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'from_status' => null,
                'to_status' => 'REGISTERED',
                'changed_by' => $admin->id,
            ]
        );

        /*
         * Audit create.
         */
        $this->assertDatabaseHas(
            'activity_logs',
            [
                'registration_id' => $registration->id,
                'user_id' => $admin->id,
                'action' => 'CREATE_REGISTRATION',
                'ip_address' => '203.0.113.50',
                'user_agent' =>
                    'SPMB-MARSA-Admin-Create-Test/1.0',
            ]
        );
    }

    public function test_system_controlled_fields_cannot_be_manipulated(): void
    {
        $admin = $this->makeUser('ADMIN');

        $payload = array_merge(
            $this->validPayload(),
            [
                /*
                * Semua nilai ini tidak boleh dipercaya.
                */
                'period_id' => $this->historicalPeriod->id,
                'data_source' => 'PUBLIC',
                'status' => 'REENROLLED',
                'created_by' => 999999,
                'registered_at' => '2020-01-01 00:00:00',
                'registration_number' => 'HACKED-001',
                'public_token' => '01AAAAAAAAAAAAAAAAAAAAAAAA',
            ]
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route('admin.registrations.store'),
                $payload
            );

        $response->assertSessionHasNoErrors();

        $registration = DB::table('registrations')
            ->where('nik', '3377777777000001')
            ->first();

        $this->assertNotNull($registration);

        /*
        * Periode harus berasal dari Admin Period Context,
        * bukan dari payload.
        */
        $this->assertSame(
            $this->activePeriod->id,
            $registration->period_id
        );

        $this->assertSame(
            'ADMIN',
            $registration->data_source
        );

        $this->assertSame(
            'REGISTERED',
            $registration->status
        );

        $this->assertSame(
            $admin->id,
            $registration->created_by
        );

        $this->assertNotSame(
            'HACKED-001',
            $registration->registration_number
        );

        $this->assertNotSame(
            '01AAAAAAAAAAAAAAAAAAAAAAAA',
            $registration->public_token
        );
    }

    public function test_admission_path_id_from_client_is_ignored(): void
    {
        $admin = $this->makeUser('ADMIN');

        $otherPeriod = $this->makePeriod(
            '2028/2029',
            2028,
            false,
            'OPEN'
        );

        $otherPath = AdmissionPath::query()->create([
            'period_id' => $otherPeriod->id,
            'name' => 'Umum 2028',
            'code' => 'UMUM-2028',
            'start_date' => '2028-01-01',
            'end_date' => '2028-06-30',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $payload = array_merge(
            $this->validPayload(),
            [
                'admission_path_id' => $otherPath->id,
            ]
        );

        $this->actingAs($admin)
            ->post(
                route('admin.registrations.store'),
                $payload
            )
            ->assertSessionHasNoErrors();

        $registration = Registration::query()
            ->where(
                'period_id',
                $this->activePeriod->id
            )
            ->where(
                'nik',
                '3377777777000001'
            )
            ->firstOrFail();

        $this->assertSame(
            $this->specialPath->id,
            $registration->admission_path_id
        );

        $this->assertNotSame(
            $otherPath->id,
            $registration->admission_path_id
        );
    }

    public function test_current_active_admission_path_is_preferred_for_admin(): void
    {
        $admin = $this->makeUser('ADMIN');

        $currentPath = AdmissionPath::query()->create([
            'period_id' => $this->activePeriod->id,
            'name' => 'Umum Saat Ini',
            'code' => 'UMUM-CURRENT',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $this->actingAs($admin)
            ->post(
                route('admin.registrations.store'),
                $this->validPayload()
            )
            ->assertSessionHasNoErrors();

        $registration = Registration::query()
            ->where(
                'period_id',
                $this->activePeriod->id
            )
            ->where(
                'nik',
                '3377777777000001'
            )
            ->firstOrFail();

        $this->assertSame(
            $currentPath->id,
            $registration->admission_path_id
        );

        $this->assertNotSame(
            $this->specialPath->id,
            $registration->admission_path_id
        );
    }

    public function test_admin_gets_friendly_error_when_no_admission_path_is_available(): void
    {
        $admin = $this->makeUser('ADMIN');

        $this->specialPath->update([
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        $response = $this
            ->from(route(
                'admin.registrations.create',
                [
                    'period_id' => $this->activePeriod->id,
                ]
            ))
            ->actingAs($admin)
            ->post(
                route('admin.registrations.store'),
                $this->validPayload()
            );

        $response
            ->assertRedirect(route(
                'admin.registrations.create',
                [
                    'period_id' => $this->activePeriod->id,
                ]
            ))
            ->assertSessionHasErrors('admission_path');

        $this->assertDatabaseMissing(
            'registrations',
            [
                'period_id' => $this->activePeriod->id,
                'nik' => '3377777777000001',
            ]
        );
    }

    public function test_nik_must_be_unique_within_same_period(): void
    {
        $admin = $this->makeUser('ADMIN');

        /*
        * Pendaftaran pertama valid.
        */
        $this->actingAs($admin)
            ->post(
                route('admin.registrations.store'),
                $this->validPayload()
            )
            ->assertSessionHasNoErrors();

        /*
        * Pendaftaran kedua memakai NIK yang sama
        * pada periode yang sama.
        */
        $duplicatePayload = array_merge(
            $this->validPayload(),
            [
                'full_name' => 'SISWA DUPLIKAT',
                'whatsapp' => '081277770002',
            ]
        );

        $this->actingAs($admin)
            ->post(
                route('admin.registrations.store'),
                $duplicatePayload
            )
            ->assertSessionHasErrors('nik');

        $this->assertSame(
            1,
            DB::table('registrations')
                ->where(
                    'period_id',
                    $this->activePeriod->id
                )
                ->where(
                    'nik',
                    '3377777777000001'
                )
                ->count()
        );
    }

    public function test_major_from_other_period_is_rejected(): void
    {
        $admin = $this->makeUser('ADMIN');

        $otherPeriod = $this->makePeriod(
            '2028/2029',
            2028,
            false,
            'OPEN'
        );

        $otherMajor = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Teknik Kendaraan Ringan',
            'code' => 'TKRO',
            'is_active' => true,
        ]);

        $otherPeriod->majors()->attach(
            $otherMajor->id,
            [
                'is_active' => true,
            ]
        );

        $payload = array_merge(
            $this->validPayload(),
            [
                'major_id' => $otherMajor->id,
            ]
        );

        $this->actingAs($admin)
            ->post(
                route('admin.registrations.store'),
                $payload
            )
            ->assertSessionHasErrors('major_id');

        $this->assertDatabaseMissing(
            'registrations',
            [
                'period_id' => $this->activePeriod->id,
                'nik' => '3377777777000001',
            ]
        );
    }

    public function test_inactive_origin_school_is_rejected(): void
    {
        $admin = $this->makeUser('ADMIN');

        $inactiveOriginSchoolId = DB::table('origin_schools')
            ->insertGetId([
                'name' => 'SMP NONAKTIF CREATE TEST',
                'type' => 'SMP',
                'is_active' => false,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $payload = array_merge(
            $this->validPayload(),
            [
                'origin_school_id' =>
                    (string) $inactiveOriginSchoolId,
            ]
        );

        $this->actingAs($admin)
            ->post(
                route('admin.registrations.store'),
                $payload
            )
            ->assertSessionHasErrors(
                'origin_school_id'
            );

        $this->assertDatabaseMissing(
            'registrations',
            [
                'period_id' => $this->activePeriod->id,
                'nik' => '3377777777000001',
            ]
        );
    }

    public function test_relief_option_not_available_on_active_period_is_rejected(): void
    {
        $admin = $this->makeUser('ADMIN');

        $reliefOptionId = DB::table('relief_options')
            ->insertGetId([
                'name' => 'Prestasi Test',
                'slug' => 'prestasi-create-test',
                'description' => null,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        /*
        * Sengaja TIDAK di-attach ke activePeriod.
        */
        $payload = array_merge(
            $this->validPayload(),
            [
                'relief_options' => [
                    $reliefOptionId,
                ],
            ]
        );

        $this->actingAs($admin)
            ->post(
                route('admin.registrations.store'),
                $payload
            )
            ->assertSessionHasErrors(
                'relief_options.0'
            );

        $this->assertDatabaseMissing(
            'registrations',
            [
                'period_id' => $this->activePeriod->id,
                'nik' => '3377777777000001',
            ]
        );
    }

    public function test_special_program_not_available_on_active_period_is_rejected(): void
    {
        $admin = $this->makeUser('ADMIN');

        $specialProgramId = DB::table('special_programs')
            ->insertGetId([
                'name' => 'Program Khusus Test',
                'slug' => 'program-khusus-create-test',
                'description' => null,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        /*
        * Sengaja TIDAK di-attach ke activePeriod.
        */
        $payload = array_merge(
            $this->validPayload(),
            [
                'special_programs' => [
                    $specialProgramId,
                ],
            ]
        );

        $this->actingAs($admin)
            ->post(
                route('admin.registrations.store'),
                $payload
            )
            ->assertSessionHasErrors(
                'special_programs.0'
            );

        $this->assertDatabaseMissing(
            'registrations',
            [
                'period_id' => $this->activePeriod->id,
                'nik' => '3377777777000001',
            ]
        );
    }

    public function test_valid_relief_and_special_program_are_saved(): void
    {
        $this->activePeriod->update([
            'enable_achievement_relief' => true,
        ]);

        $admin = $this->makeUser('ADMIN');

        $reliefOptionId = DB::table('relief_options')
            ->insertGetId([
                'name' => 'Prestasi Aktif Test',
                'slug' => 'prestasi-aktif-create-test',
                'description' => null,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('period_relief_options')
            ->insert([
                'ppdb_period_id' => $this->activePeriod->id,
                'relief_option_id' => $reliefOptionId,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $specialProgramId = DB::table('special_programs')
            ->insertGetId([
                'name' => 'Program Aktif Test',
                'slug' => 'program-aktif-create-test',
                'description' => null,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('period_special_programs')
            ->insert([
                'ppdb_period_id' => $this->activePeriod->id,
                'special_program_id' => $specialProgramId,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $payload = array_merge(
            $this->validPayload(),
            [
                'relief_options' => [
                    $reliefOptionId,
                ],
                'special_programs' => [
                    $specialProgramId,
                ],
            ]
        );

        $this->actingAs($admin)
            ->post(
                route('admin.registrations.store'),
                $payload
            )
            ->assertSessionHasNoErrors();

        $registrationId = DB::table('registrations')
            ->where('period_id', $this->activePeriod->id)
            ->where('nik', '3377777777000001')
            ->value('id');

        $this->assertNotNull($registrationId);

        $this->assertDatabaseHas(
            'registration_relief_options',
            [
                'registration_id' => $registrationId,
                'relief_option_id' => $reliefOptionId,
            ]
        );

        $this->assertDatabaseHas(
            'registration_special_programs',
            [
                'registration_id' => $registrationId,
                'special_program_id' => $specialProgramId,
            ]
        );
    }

    public function test_active_open_period_index_shows_add_registration_button(): void
    {
        $admin = $this->makeUser('ADMIN');

        $this->actingAs($admin)
        ->get(route('admin.registrations.index', [
            'period_id' => $this->activePeriod->id,
        ]))
        ->assertOk()
        ->assertSee('Tambah Pendaftar')
        ->assertSee(
            route('admin.registrations.create', [
                'period_id' => $this->activePeriod->id,
            ]),
            false
        );
    }

    public function test_historical_period_index_hides_add_registration_button(): void
    {
        $admin = $this->makeUser('ADMIN');

        $this->actingAs($admin)
            ->get(route('admin.registrations.index', [
                'period_id' => $this->historicalPeriod->id,
            ]))
            ->assertOk()
            ->assertDontSee('Tambah Pendaftar');
    }

    public function test_admin_can_store_different_parent_address(): void
    {
        $payload = array_merge($this->validPayload(), [
            'same_parent_address' => '0',
            'parent_hamlet' => 'DUKUH MELATI',
            'parent_rt' => '003',
            'parent_rw' => '004',
            'parent_village' => 'DESA MELATI',
            'parent_district' => 'GOMBONG',
            'parent_city' => 'KEBUMEN',
            'parent_province' => 'JAWA TENGAH',
            'parent_postal_code' => '54411',
        ]);

        $this->actingAs($this->makeUser('ADMIN'))
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'period_id' => $this->activePeriod->id,
            'nik' => $payload['nik'],
            'parent_hamlet' => 'DUKUH MELATI',
            'parent_rt' => '003',
            'parent_rw' => '004',
            'parent_village' => 'DESA MELATI',
            'parent_district' => 'GOMBONG',
            'parent_city' => 'KEBUMEN',
            'parent_province' => 'JAWA TENGAH',
            'parent_postal_code' => '54411',
        ]);
    }

    public function test_admin_checkbox_copies_student_address_on_server(): void
    {
        $payload = array_merge($this->validPayload(), [
            'same_parent_address' => '1',
            'parent_hamlet' => 'ALAMAT MANIPULASI',
            'parent_rt' => '999',
            'parent_rw' => '999',
            'parent_village' => 'DESA LAIN',
            'parent_district' => 'KECAMATAN LAIN',
            'parent_city' => 'KOTA LAIN',
            'parent_province' => 'PROVINSI LAIN',
            'parent_postal_code' => '99999',
        ]);

        $this->actingAs($this->makeUser('ADMIN'))
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'period_id' => $this->activePeriod->id,
            'nik' => $payload['nik'],
            'parent_hamlet' => $payload['hamlet'],
            'parent_rt' => $payload['rt'],
            'parent_rw' => $payload['rw'],
            'parent_village' => $payload['village'],
            'parent_district' => $payload['district'],
            'parent_city' => $payload['city'],
            'parent_province' => $payload['province'],
            'parent_postal_code' => $payload['postal_code'],
        ]);
    }

    public function test_create_form_only_lists_active_referral_sources(): void
    {
        \App\Models\ReferralSource::query()->create([
            'name' => 'WALI MURID',
            'is_active' => true,
        ]);

        \App\Models\ReferralSource::query()->create([
            'name' => 'SUMBER NONAKTIF',
            'is_active' => false,
        ]);

        $this->actingAs($this->makeUser('ADMIN'))
            ->get(route('admin.registrations.create', [
                'period_id' => $this->activePeriod->id,
            ]))
            ->assertOk()
            ->assertSee('value="' . \App\Models\ReferralSource::query()->where('name', 'ALUMNI')->firstOrFail()->id . '"', false)
            ->assertSee('value="' . \App\Models\ReferralSource::query()->where('name', 'WALI MURID')->firstOrFail()->id . '"', false)
            ->assertDontSee('value="' . \App\Models\ReferralSource::query()->where('name', 'SUMBER NONAKTIF')->firstOrFail()->id . '"', false);
    }

    public function test_create_rejects_inactive_referral_source(): void
    {
        \App\Models\ReferralSource::query()->create([
            'name' => 'SUMBER NONAKTIF',
            'is_active' => false,
        ]);

        $payload = $this->validPayload();
        $payload['referrer_source'] = 'SUMBER NONAKTIF';

        $this->actingAs($this->makeUser('ADMIN'))
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasErrors('referrer_source');
    }

    public function test_create_rejects_unknown_referral_source(): void
    {
        $payload = $this->validPayload();
        $payload['referrer_source'] = 'SUMBER TIDAK TERDAFTAR';

        $this->actingAs($this->makeUser('ADMIN'))
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasErrors('referrer_source');
    }

    public function test_hybrid_active_student_requires_referrer_class(): void
    {
        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'SISWA AKTIF',
            'code' => 'ACTIVE_STUDENT',
            'is_active' => true,
        ]);

        $payload = $this->validPayload();
        $payload['referral_source_id'] = $source->id;
        $payload['referrer_source'] = $source->name;
        unset($payload['referrer_class']);

        $this->actingAs($this->makeUser('ADMIN'))
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasErrors('referrer_class');

        $this->assertDatabaseMissing('registrations', [
            'period_id' => $this->activePeriod->id,
            'nik' => $payload['nik'],
        ]);
    }

    public function test_hybrid_rejects_referrer_class_for_non_student_category(): void
    {
        $payload = $this->validPayload();
        $payload['referrer_class'] = 'XII RPL 1';

        $this->actingAs($this->makeUser('ADMIN'))
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasErrors('referrer_class');

        $this->assertDatabaseMissing('registrations', [
            'period_id' => $this->activePeriod->id,
            'nik' => $payload['nik'],
        ]);
    }

    public function test_hybrid_prospective_student_requires_registration_reference(): void
    {
        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'CALON SISWA',
            'code' => 'PROSPECTIVE_STUDENT',
            'is_active' => true,
        ]);

        $payload = $this->validPayload();
        $payload['referral_source_id'] = $source->id;
        $payload['referrer_source'] = $source->name;
        unset($payload['referrer_registration_id']);

        $this->actingAs($this->makeUser('ADMIN'))
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasErrors('referrer_registration_id');

        $this->assertDatabaseMissing('registrations', [
            'period_id' => $this->activePeriod->id,
            'nik' => $payload['nik'],
        ]);
    }

    public function test_hybrid_rejects_prospective_student_from_different_period(): void
    {
        $historicalPath = AdmissionPath::query()->create([
            'period_id' => $this->historicalPeriod->id,
            'name' => 'Reguler Historis',
            'code' => 'REG-HIST',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $historicalRegistration = Registration::query()->create([
            'period_id' => $this->historicalPeriod->id,
            'admission_path_id' => $historicalPath->id,
            'major_id' => $this->major->id,
            'registration_number' => 'HIST-0001',
            'nik' => '3377777777000099',
            'full_name' => 'PENDAFTAR HISTORIS',
            'whatsapp' => '081277770099',
        ]);

        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'CALON SISWA',
            'code' => 'PROSPECTIVE_STUDENT',
            'is_active' => true,
        ]);

        $payload = $this->validPayload();
        $payload['referral_source_id'] = $source->id;
        $payload['referrer_source'] = $source->name;
        $payload['referrer_registration_id'] = $historicalRegistration->id;

        $this->actingAs($this->makeUser('ADMIN'))
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasErrors('referrer_registration_id');

        $this->assertDatabaseMissing('registrations', [
            'period_id' => $this->activePeriod->id,
            'nik' => $payload['nik'],
        ]);
    }

    public function test_hybrid_rejects_mismatched_referral_source_snapshot(): void
    {
        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'SISWA AKTIF',
            'code' => 'ACTIVE_STUDENT',
            'is_active' => true,
        ]);

        $payload = $this->validPayload();
        $payload['referral_source_id'] = $source->id;
        $payload['referrer_source'] = 'ALUMNI';
        $payload['referrer_class'] = 'XII RPL 1';

        $this->actingAs($this->makeUser('ADMIN'))
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasErrors('referrer_source');

        $this->assertDatabaseMissing('registrations', [
            'period_id' => $this->activePeriod->id,
            'nik' => $payload['nik'],
        ]);
    }

    public function test_hybrid_active_student_fields_are_persisted(): void
    {
        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'SISWA AKTIF',
            'code' => 'ACTIVE_STUDENT',
            'is_active' => true,
        ]);

        $payload = $this->validPayload();
        $payload['referral_source_id'] = $source->id;
        $payload['referrer_source'] = $source->name;
        $payload['referrer_class'] = 'XII RPL 1';
        $payload['referrer_notes'] = 'REFERENSI DARI SISWA AKTIF';

        $this->actingAs($this->makeUser('ADMIN'))
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'period_id' => $this->activePeriod->id,
            'nik' => $payload['nik'],
            'referral_source_id' => $source->id,
            'referrer_source' => 'SISWA AKTIF',
            'referrer_name' => 'PAK AHMAD',
            'referrer_class' => 'XII RPL 1',
            'referrer_notes' => 'REFERENSI DARI SISWA AKTIF',
        ]);
    }

    public function test_hybrid_prospective_student_reference_is_persisted(): void
    {
        $admin = $this->makeUser('ADMIN');
        $firstPayload = $this->validPayload();

        $this->actingAs($admin)
            ->post(route('admin.registrations.store'), $firstPayload)
            ->assertSessionHasNoErrors();

        $first = Registration::query()
            ->where('period_id', $this->activePeriod->id)
            ->where('nik', $firstPayload['nik'])
            ->firstOrFail();

        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'CALON SISWA',
            'code' => 'PROSPECTIVE_STUDENT',
            'is_active' => true,
        ]);

        $payload = $this->validPayload();
        $payload['nik'] = '3377777777000002';
        $payload['nisn'] = '1234567891';
        $payload['full_name'] = 'SISWA KEDUA';
        $payload['referral_source_id'] = $source->id;
        $payload['referrer_source'] = $source->name;
        $payload['referrer_registration_id'] = $first->id;
        $payload['referrer_notes'] = 'DIAJAK TEMAN PENDAFTAR';

        $this->actingAs($admin)
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'period_id' => $this->activePeriod->id,
            'nik' => $payload['nik'],
            'referral_source_id' => $source->id,
            'referrer_source' => 'CALON SISWA',
            'referrer_registration_id' => $first->id,
            'referrer_notes' => 'DIAJAK TEMAN PENDAFTAR',
        ]);
    }

    public function test_hybrid_create_derives_referrer_source_snapshot_from_master(): void
    {
        $source = \App\Models\ReferralSource::query()->firstOrCreate([
            'name' => 'ALUMNI',
        ], [
            'code' => 'ALUMNI',
            'is_active' => true,
        ]);

        $payload = $this->validPayload();
        $payload['referral_source_id'] = $source->id;
        unset($payload['referrer_source']);

        $this->actingAs($this->makeUser('ADMIN'))
            ->post(route('admin.registrations.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'period_id' => $this->activePeriod->id,
            'nik' => $payload['nik'],
            'referral_source_id' => $source->id,
            'referrer_source' => 'ALUMNI',
        ]);
    }

    public function test_create_form_respects_period_visibility_settings(): void
    {
        $admin = $this->makeUser('ADMIN');
        $url = route('admin.registrations.create', ['period_id' => $this->activePeriod->id]);

        $this->activePeriod->update([
            'show_graduation_score' => true,
            'enable_achievement_relief' => true,
        ]);

        $this->actingAs($admin)->get($url)->assertOk()
            ->assertSee('name="graduation_year"', false)
            ->assertSee('name="graduation_score"', false)
            ->assertSee('Keringanan / Prestasi');

        $this->activePeriod->update([
            'show_graduation_score' => false,
            'enable_achievement_relief' => false,
        ]);

        $this->actingAs($admin)->get($url)->assertOk()
            ->assertSee('name="graduation_year"', false)
            ->assertDontSee('name="graduation_score"', false)
            ->assertDontSee('Keringanan / Prestasi');
    }

    private function makeUser(string $role): User
    {
        static $sequence = 0;

        $sequence++;

        return User::factory()->create([
            'name' => $role.' CREATE TEST',
            'email' =>
                strtolower($role)
                .'-create-'
                .$sequence
                .'@example.test',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function makePeriod(
        string $name,
        int $yearStart,
        bool $isActive,
        string $status
    ): PpdbPeriod {
        return PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => $name,
            'year_start' => $yearStart,
            'year_end' => $yearStart + 1,
            'registration_open' => $yearStart.'-01-01',
            'registration_close' => $yearStart.'-06-30',
            'status' => $status,
            'is_active' => $isActive,
            'number_prefix' => 'MARSA',
            'number_year' => $yearStart,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 350000,
        ]);
    }

    private function validPayload(): array
    {
        return [
            'nik' => '3377777777000001',
            'nisn' => '1234567890',

            'full_name' => 'SISWA INPUT ADMIN',

            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',

            'gender' => 'L',
            'religion' => 'ISLAM',

            'origin_school_id' =>
                (string) $this->originSchoolId,

            'origin_school_other' => null,

            'hamlet' => 'KRAJAN',
            'rt' => '001',
            'rw' => '002',
            'village' => 'TEST VILLAGE',
            'district' => 'TEST DISTRICT',
            'city' => 'KEBUMEN',
            'province' => 'JAWA TENGAH',
            'postal_code' => '54311',

            'father_name' => 'AYAH TEST',
            'father_job' => 'WIRASWASTA',

            'mother_name' => 'IBU TEST',
            'mother_job' => 'IBU RUMAH TANGGA',

            'whatsapp' => '081277770001',

            'graduation_year' => 2026,
            'graduation_score' => 85.50,

            'major_id' =>
                $this->major->id,

            'referrer_name' => 'PAK AHMAD',
            'referrer_source' => 'ALUMNI',


            'special_programs' => [],

            'notes' => 'Input oleh petugas.',
        ];
    }
}