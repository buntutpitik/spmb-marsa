<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRegistrationEditTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PpdbPeriod $activePeriod;

    private PpdbPeriod $historicalPeriod;

    private Major $major;

    private AdmissionPath $activePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK ADMIN REGISTRATION EDIT TEST',
            'npsn' => '12345670',
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

        $this->activePath = AdmissionPath::query()->create([
            'period_id' => $this->activePeriod->id,
            'name' => 'Umum',
            'code' => 'UMUM',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        \App\Models\ReferralSource::query()->create([
            'name' => 'ALUMNI',
            'is_active' => true,
        ]);
    }

    public function test_all_operational_roles_can_open_active_registration_edit_form(): void
    {
        $registration = $this->makeRegistration(
            $this->activePeriod
        );

        foreach (
            ['SUPERADMIN', 'ADMIN', 'PANITIA', 'BENDAHARA']
            as $role
        ) {
            $user = $this->makeUser($role);

            $this->actingAs($user)
                ->get(route(
                    'admin.registrations.edit',
                    [
                        'registration' => $registration,
                        'period_id' => $this->activePeriod->id,
                    ]
                ))
                ->assertOk()
                ->assertSee('Edit Data Pendaftar')
                ->assertSee($registration->full_name);
        }
    }

    public function test_historical_registration_edit_form_is_not_available(): void
    {
        $registration = $this->makeRegistration(
            $this->historicalPeriod
        );

        $admin = $this->makeUser('ADMIN');

        $this->actingAs($admin)
            ->get(route(
                'admin.registrations.edit',
                [
                    'registration' => $registration,
                    'period_id' => $this->historicalPeriod->id,
                ]
            ))
            ->assertNotFound();
    }

    public function test_registration_cannot_be_edited_in_wrong_period_context(): void
    {
        $registration = $this->makeRegistration(
            $this->activePeriod
        );

        $admin = $this->makeUser('ADMIN');

        $this->actingAs($admin)
            ->get(route(
                'admin.registrations.edit',
                [
                    'registration' => $registration,
                    'period_id' => $this->historicalPeriod->id,
                ]
            ))
            ->assertNotFound();
    }

    public function test_admin_can_update_registration_biodata(): void
    {
        $registration = $this->makeRegistration(
            $this->activePeriod
        );

        $admin = $this->makeUser('ADMIN');

        $payload = $this->validPayload($registration);

        $payload['full_name'] = 'NAMA SISWA DIPERBAIKI';
        $payload['nisn'] = '1234567890';
        $payload['birth_place'] = 'PURWOREJO';
        $payload['whatsapp'] = '081234567890';
        $payload['father_name'] = 'AYAH DIPERBAIKI';
        $payload['referrer_name'] = 'PAK AHMAD';
        $payload['referrer_source'] = 'ALUMNI';

        $this->actingAs($admin)
            ->put(route(
                'admin.registrations.update',
                [
                    'registration' => $registration,
                    'period_id' => $this->activePeriod->id,
                ]
            ), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route(
                'admin.registrations.show',
                [
                    'registration' => $registration,
                    'period_id' => $this->activePeriod->id,
                ]
            ));

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'full_name' => 'NAMA SISWA DIPERBAIKI',
            'nisn' => '1234567890',
            'birth_place' => 'PURWOREJO',
            'whatsapp' => '081234567890',
            'father_name' => 'AYAH DIPERBAIKI',
            'referrer_name' => 'PAK AHMAD',
            'referrer_source' => 'ALUMNI',
        ]);
    }

    public function test_system_fields_cannot_be_changed_through_registration_edit(): void
    {
        $registration = $this->makeRegistration(
            $this->activePeriod
        );

        $admin = $this->makeUser('ADMIN');

        $originalRegistrationNumber =
            $registration->registration_number;

        $originalStatus =
            $registration->status;

        $originalDataSource =
            $registration->data_source;

        $originalRegisteredAt =
            $registration->registered_at?->toDateTimeString();

        $payload = $this->validPayload($registration);

        /*
         * Manipulated fields.
         *
         * Form normal tidak akan mengirim field-field ini,
         * tetapi server tetap harus aman bila request
         * dimanipulasi secara manual.
         */
        $payload['registration_number'] =
            'HACKED-REGISTRATION-NUMBER';

        $payload['public_token'] =
            '01HACKEDPUBLICTOKEN00000000';

        $payload['status'] =
            'REENROLLED';

        $payload['data_source'] =
            'PUBLIC';

        $payload['registered_at'] =
            '2000-01-01 00:00:00';

        $payload['accepted_at'] =
            '2000-01-01 00:00:00';

        $payload['reenrolled_at'] =
            '2000-01-01 00:00:00';

        $this->actingAs($admin)
            ->put(route(
                'admin.registrations.update',
                [
                    'registration' => $registration,
                    'period_id' => $this->activePeriod->id,
                ]
            ), $payload)
            ->assertSessionHasNoErrors();

        $registration->refresh();

        $this->assertSame(
            $originalRegistrationNumber,
            $registration->registration_number
        );

        $this->assertSame(
            $originalStatus,
            $registration->status
        );

        $this->assertSame(
            $originalDataSource,
            $registration->data_source
        );

        $this->assertSame(
            $originalRegisteredAt,
            $registration->registered_at?->toDateTimeString()
        );

        $this->assertNull($registration->accepted_at);
        $this->assertNull($registration->reenrolled_at);
    }

    public function test_historical_registration_cannot_be_updated(): void
    {
        $registration = $this->makeRegistration(
            $this->historicalPeriod
        );

        $admin = $this->makeUser('ADMIN');

        $originalName = $registration->full_name;

        $payload = $this->validPayload($registration);
        $payload['full_name'] = 'TIDAK BOLEH BERUBAH';

        $this->actingAs($admin)
            ->put(route(
                'admin.registrations.update',
                [
                    'registration' => $registration,
                    'period_id' => $this->historicalPeriod->id,
                ]
            ), $payload)
            ->assertNotFound();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'full_name' => $originalName,
        ]);
    }

    public function test_registration_update_creates_activity_log(): void
    {
        $registration = $this->makeRegistration(
            $this->activePeriod
        );

        $admin = $this->makeUser('ADMIN');

        $payload = $this->validPayload($registration);
        $payload['full_name'] = 'NAMA SETELAH EDIT';

        $this->actingAs($admin)
            ->put(route(
                'admin.registrations.update',
                [
                    'registration' => $registration,
                    'period_id' => $this->activePeriod->id,
                ]
            ), $payload)
            ->assertSessionHasNoErrors();

        $log = ActivityLog::query()
            ->where('registration_id', $registration->id)
            ->where('user_id', $admin->id)
            ->where('action', 'UPDATE_REGISTRATION')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);

        $this->assertSame(
            'Data pendaftar diperbarui.',
            $log->description
        );

        $this->assertSame(
            $registration->registration_number,
            $log->metadata['registration_number'] ?? null
        );

        $this->assertArrayHasKey(
            'changes',
            $log->metadata ?? []
        );
    }

    public function test_all_operational_roles_can_update_active_registration(): void
    {
        foreach (['SUPERADMIN', 'ADMIN', 'PANITIA', 'BENDAHARA'] as $role) {
            $registration = $this->makeRegistration($this->activePeriod);
            $user = $this->makeUser($role);
            $payload = $this->validPayload($registration);

            $payload['full_name'] = 'EDIT OLEH '.$role;

            $this->actingAs($user)
                ->put(route('admin.registrations.update', [
                    'registration' => $registration,
                    'period_id' => $this->activePeriod->id,
                ]), $payload)
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('registrations', [
                'id' => $registration->id,
                'full_name' => 'EDIT OLEH '.$role,
            ]);
        }
    }

    public function test_nik_must_remain_unique_inside_same_period(): void
    {
        $first = $this->makeRegistration($this->activePeriod);
        $second = $this->makeRegistration($this->activePeriod);
        $admin = $this->makeUser('ADMIN');

        $payload = $this->validPayload($second);
        $payload['nik'] = $first->nik;

        $this->actingAs($admin)
            ->put(route('admin.registrations.update', [
                'registration' => $second,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasErrors('nik');
    }

    public function test_same_nik_is_allowed_for_same_registration_during_edit(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);
        $admin = $this->makeUser('ADMIN');
        $payload = $this->validPayload($registration);

        $this->actingAs($admin)
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasNoErrors();
    }

    public function test_major_from_another_period_cannot_be_used(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);
        $admin = $this->makeUser('ADMIN');

        $otherMajor = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Teknik Sepeda Motor',
            'code' => 'TSM',
            'is_active' => true,
        ]);

        $this->historicalPeriod->majors()->attach(
            $otherMajor->id,
            ['is_active' => true]
        );

        $payload = $this->validPayload($registration);
        $payload['major_id'] = $otherMajor->id;

        $this->actingAs($admin)
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertStatus(422);
    }

    public function test_admission_path_from_another_period_cannot_be_used(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);
        $admin = $this->makeUser('ADMIN');

        $otherPath = AdmissionPath::query()->create([
            'period_id' => $this->historicalPeriod->id,
            'name' => 'Khusus Historis',
            'code' => 'KHUSUS-HIST',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $payload = $this->validPayload($registration);
        $payload['admission_path_id'] = $otherPath->id;

        $this->actingAs($admin)
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertStatus(422);
    }

    public function test_relief_options_and_special_programs_can_be_synced(): void
    {
        $this->activePeriod->update([
            'enable_achievement_relief' => true,
        ]);

        $registration = $this->makeRegistration($this->activePeriod);
        $admin = $this->makeUser('ADMIN');

        $relief = \App\Models\ReliefOption::query()->create([
            'name' => 'Prestasi Akademik',
            'slug' => 'prestasi-akademik',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $program = \App\Models\SpecialProgram::query()->create([
            'name' => 'Program Tahfidz',
            'slug' => 'program-tahfidz',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->activePeriod->reliefOptions()->attach(
            $relief->id,
            [
                'is_active' => true,
                'sort_order' => 10,
            ]
        );

        $this->activePeriod->specialPrograms()->attach(
            $program->id,
            [
                'is_active' => true,
                'sort_order' => 10,
            ]
        );

        $payload = $this->validPayload($registration);
        $payload['relief_options'] = [$relief->id];
        $payload['special_programs'] = [$program->id];

        $this->actingAs($admin)
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registration_relief_options', [
            'registration_id' => $registration->id,
            'relief_option_id' => $relief->id,
        ]);

        $this->assertDatabaseHas('registration_special_programs', [
            'registration_id' => $registration->id,
            'special_program_id' => $program->id,
        ]);
    }

    public function test_active_registration_detail_shows_edit_data_button(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);
        $admin = $this->makeUser('ADMIN');

        $this->actingAs($admin)
            ->get(route('admin.registrations.show', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]))
            ->assertOk()
            ->assertSee('Edit Data')
            ->assertSee(
                route('admin.registrations.edit', [
                    'registration' => $registration,
                    'period_id' => $this->activePeriod->id,
                ]),
                false
            );
    }

    public function test_historical_registration_detail_does_not_show_edit_data_button(): void
    {
        $registration = $this->makeRegistration($this->historicalPeriod);
        $admin = $this->makeUser('ADMIN');

        $this->actingAs($admin)
            ->get(route('admin.registrations.show', [
                'registration' => $registration,
                'period_id' => $this->historicalPeriod->id,
            ]))
            ->assertOk()
            ->assertDontSee('Edit Data');
    }

    public function test_admin_can_update_parent_address(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);
        $payload = $this->validPayload($registration);

        $payload['same_parent_address'] = '0';
        $payload['parent_hamlet'] = 'DUKUH MAWAR';
        $payload['parent_rt'] = '005';
        $payload['parent_rw'] = '006';
        $payload['parent_village'] = 'DESA MAWAR';
        $payload['parent_district'] = 'GOMBONG';
        $payload['parent_city'] = 'KEBUMEN';
        $payload['parent_province'] = 'JAWA TENGAH';
        $payload['parent_postal_code'] = '54411';

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'parent_hamlet' => 'DUKUH MAWAR',
            'parent_rt' => '005',
            'parent_rw' => '006',
            'parent_village' => 'DESA MAWAR',
            'parent_district' => 'GOMBONG',
            'parent_city' => 'KEBUMEN',
            'parent_province' => 'JAWA TENGAH',
            'parent_postal_code' => '54411',
        ]);
    }

    public function test_admin_edit_preserves_parent_address_when_not_submitted(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);

        $registration->update([
            'parent_hamlet' => 'DUKUH LAMA',
            'parent_rt' => '007',
            'parent_rw' => '008',
            'parent_village' => 'DESA LAMA',
            'parent_district' => 'KEBUMEN',
            'parent_city' => 'KEBUMEN',
            'parent_province' => 'JAWA TENGAH',
            'parent_postal_code' => '54311',
        ]);

        $payload = $this->validPayload($registration);

        unset($payload['same_parent_address']);

        foreach ([
            'parent_hamlet',
            'parent_rt',
            'parent_rw',
            'parent_village',
            'parent_district',
            'parent_city',
            'parent_province',
            'parent_postal_code',
        ] as $field) {
            unset($payload[$field]);
        }

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'parent_hamlet' => 'DUKUH LAMA',
            'parent_rt' => '007',
            'parent_rw' => '008',
            'parent_village' => 'DESA LAMA',
            'parent_district' => 'KEBUMEN',
            'parent_city' => 'KEBUMEN',
            'parent_province' => 'JAWA TENGAH',
            'parent_postal_code' => '54311',
        ]);
    }

    public function test_admin_edit_checkbox_copies_updated_student_address(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);

        $registration->update([
            'parent_hamlet' => 'ALAMAT ORANG TUA LAMA',
            'parent_city' => 'PURWOREJO',
        ]);

        $payload = $this->validPayload($registration);

        $payload['same_parent_address'] = '1';
        $payload['hamlet'] = 'DUKUH BARU';
        $payload['rt'] = '011';
        $payload['rw'] = '012';
        $payload['village'] = 'DESA BARU';
        $payload['district'] = 'GOMBONG';
        $payload['city'] = 'KEBUMEN';
        $payload['province'] = 'JAWA TENGAH';
        $payload['postal_code'] = '54411';

        $payload['parent_hamlet'] = 'ALAMAT MANIPULASI';
        $payload['parent_city'] = 'KOTA LAIN';

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'parent_hamlet' => 'DUKUH BARU',
            'parent_rt' => '011',
            'parent_rw' => '012',
            'parent_village' => 'DESA BARU',
            'parent_district' => 'GOMBONG',
            'parent_city' => 'KEBUMEN',
            'parent_province' => 'JAWA TENGAH',
            'parent_postal_code' => '54411',
        ]);
    }

    public function test_edit_rejects_inactive_referral_source_as_new_value(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);

        \App\Models\ReferralSource::query()->create([
            'name' => 'SUMBER NONAKTIF',
            'is_active' => false,
        ]);

        $payload = $this->validPayload($registration);
        $payload['referrer_source'] = 'SUMBER NONAKTIF';

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasErrors('referrer_source');

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referrer_source' => null,
        ]);
    }

    public function test_edit_preserves_inactive_historical_referral_source(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);

        $registration->update([
            'referrer_source' => 'SUMBER LAMA',
        ]);

        \App\Models\ReferralSource::query()->create([
            'name' => 'SUMBER LAMA',
            'is_active' => false,
        ]);

        $registration->refresh();

        $this->actingAs($this->makeUser('ADMIN'))
            ->get(route('admin.registrations.edit', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]))
            ->assertOk()
            ->assertSee('Asal Pembawa historis: SUMBER LAMA (belum terhubung ke master)')
            ->assertSee('name="referral_source_id"', false);

        $payload = $this->validPayload($registration);

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referrer_source' => 'SUMBER LAMA',
        ]);
    }

    public function test_edit_rejects_unknown_referral_source_from_another_registration(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);

        $payload = $this->validPayload($registration);
        $payload['referrer_source'] = 'SUMBER HISTORIS LAIN';

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasErrors('referrer_source');

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referrer_source' => null,
        ]);
    }

    public function test_edit_preserves_historical_referral_source_missing_from_master(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);

        $registration->update([
            'referrer_source' => 'SUMBER HISTORIS DIHAPUS',
        ]);

        $registration->refresh();

        $this->actingAs($this->makeUser('ADMIN'))
            ->get(route('admin.registrations.edit', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]))
            ->assertOk()
            ->assertSee('Asal Pembawa historis: SUMBER HISTORIS DIHAPUS (belum terhubung ke master)')
            ->assertSee('name="referral_source_id"', false);

        $payload = $this->validPayload($registration);

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referrer_source' => 'SUMBER HISTORIS DIHAPUS',
        ]);
    }

    public function test_edit_can_select_another_active_referral_source(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);

        \App\Models\ReferralSource::query()->create([
            'name' => 'WALI MURID',
            'is_active' => true,
        ]);

        \App\Models\ReferralSource::query()->create([
            'name' => 'SUMBER NONAKTIF',
            'is_active' => false,
        ]);

        $this->actingAs($this->makeUser('ADMIN'))
            ->get(route('admin.registrations.edit', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]))
            ->assertOk()
            ->assertSee('name="referral_source_id"', false)
            ->assertSee('WALI MURID')
            ->assertDontSee('>SUMBER NONAKTIF</option>', false);

        $payload = $this->validPayload($registration);
        $payload['referrer_source'] = 'WALI MURID';

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referrer_source' => 'WALI MURID',
        ]);
    }

    public function test_editing_biodata_preserves_existing_hybrid_referrer_fields(): void
    {
        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'SISWA AKTIF',
            'code' => 'ACTIVE_STUDENT',
            'is_active' => true,
        ]);

        $registration = $this->makeRegistration($this->activePeriod);
        $registration->update([
            'referral_source_id' => $source->id,
            'referrer_name' => 'PAK AHMAD',
            'referrer_source' => $source->name,
            'referrer_class' => 'XII RPL 1',
            'referrer_notes' => 'REFERENSI SISWA AKTIF',
        ]);

        $payload = $this->validPayload($registration);
        $payload['full_name'] = 'NAMA SISWA DIPERBAIKI';

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'full_name' => 'NAMA SISWA DIPERBAIKI',
            'referral_source_id' => $source->id,
            'referrer_name' => 'PAK AHMAD',
            'referrer_source' => 'SISWA AKTIF',
            'referrer_class' => 'XII RPL 1',
            'referrer_notes' => 'REFERENSI SISWA AKTIF',
        ]);
    }

    public function test_edit_rejects_inactive_hybrid_referral_source_as_new_value(): void
    {
        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'KATEGORI HYBRID NONAKTIF',
            'code' => 'INACTIVE_TEST',
            'is_active' => false,
        ]);

        $registration = $this->makeRegistration($this->activePeriod);
        $payload = $this->validPayload($registration);
        $payload['referral_source_id'] = $source->id;

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasErrors('referral_source_id');

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referral_source_id' => null,
            'referrer_source' => $registration->referrer_source,
        ]);
    }

    public function test_edit_preserves_existing_inactive_hybrid_referral_source(): void
    {
        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'SISWA AKTIF',
            'code' => 'ACTIVE_STUDENT',
            'is_active' => false,
        ]);

        $registration = $this->makeRegistration($this->activePeriod);
        $registration->update([
            'referral_source_id' => $source->id,
            'referrer_name' => 'PAK AHMAD',
            'referrer_source' => 'SISWA AKTIF',
            'referrer_class' => 'XII RPL 1',
        ]);

        $payload = $this->validPayload($registration);
        $payload['full_name'] = 'BIODATA DIPERBARUI';
        $payload['referral_source_id'] = $source->id;

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'full_name' => 'BIODATA DIPERBARUI',
            'referral_source_id' => $source->id,
            'referrer_source' => 'SISWA AKTIF',
            'referrer_class' => 'XII RPL 1',
        ]);
    }

    public function test_edit_new_active_student_category_requires_referrer_class(): void
    {
        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'SISWA AKTIF',
            'code' => 'ACTIVE_STUDENT',
            'is_active' => true,
        ]);

        $registration = $this->makeRegistration($this->activePeriod);
        $payload = $this->validPayload($registration);
        $payload['referral_source_id'] = $source->id;
        $payload['referrer_source'] = $source->name;
        unset($payload['referrer_class']);

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasErrors('referrer_class');

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referral_source_id' => null,
            'referrer_class' => null,
        ]);
    }

    public function test_edit_new_prospective_student_category_requires_registration_reference(): void
    {
        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'CALON SISWA',
            'code' => 'PROSPECTIVE_STUDENT',
            'is_active' => true,
        ]);

        $registration = $this->makeRegistration($this->activePeriod);
        $payload = $this->validPayload($registration);
        $payload['referral_source_id'] = $source->id;
        $payload['referrer_source'] = $source->name;
        unset($payload['referrer_registration_id']);

        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]), $payload)
            ->assertSessionHasErrors('referrer_registration_id');

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referral_source_id' => null,
            'referrer_registration_id' => null,
        ]);
    }

    public function test_hybrid_edit_changes_snapshot_and_clears_old_student_class(): void
    {
        $studentSource = \App\Models\ReferralSource::query()->firstOrCreate(['name' => 'SISWA AKTIF'], ['code' => 'ACTIVE_STUDENT', 'is_active' => true]);
        $alumniSource = \App\Models\ReferralSource::query()->firstOrCreate(['name' => 'ALUMNI'], ['code' => 'ALUMNI', 'is_active' => true]);
        $registration = $this->makeRegistration($this->activePeriod);
        $registration->update(['referral_source_id' => $studentSource->id, 'referrer_source' => 'SISWA AKTIF', 'referrer_class' => 'XII RPL 1']);
        $payload = $this->validPayload($registration);
        $payload['referral_source_id'] = $alumniSource->id;
        unset($payload['referrer_source']);
        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', ['registration' => $registration, 'period_id' => $this->activePeriod->id]), $payload)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'referral_source_id' => $alumniSource->id, 'referrer_source' => 'ALUMNI', 'referrer_class' => null, 'referrer_registration_id' => null]);
    }

    public function test_hybrid_edit_clears_old_prospective_student_reference(): void
    {
        $prospectiveSource = \App\Models\ReferralSource::query()->firstOrCreate(['name' => 'CALON SISWA'], ['code' => 'PROSPECTIVE_STUDENT', 'is_active' => true]);
        $alumniSource = \App\Models\ReferralSource::query()->firstOrCreate(['name' => 'ALUMNI'], ['code' => 'ALUMNI', 'is_active' => true]);
        $referrer = $this->makeRegistration($this->activePeriod);
        $registration = $this->makeRegistration($this->activePeriod);
        $registration->update(['referral_source_id' => $prospectiveSource->id, 'referrer_source' => 'CALON SISWA', 'referrer_registration_id' => $referrer->id]);
        $payload = $this->validPayload($registration);
        $payload['referral_source_id'] = $alumniSource->id;
        unset($payload['referrer_source']);
        $this->actingAs($this->makeUser('ADMIN'))
            ->put(route('admin.registrations.update', ['registration' => $registration, 'period_id' => $this->activePeriod->id]), $payload)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'referral_source_id' => $alumniSource->id, 'referrer_source' => 'ALUMNI', 'referrer_registration_id' => null, 'referrer_class' => null]);
        $this->assertDatabaseHas('registrations', ['id' => $referrer->id]);
    }

    private function makeUser(string $role): User
    {
        static $sequence = 0;

        $sequence++;

        return User::factory()->create([
            'name' => $role.' EDIT TEST',
            'email' =>
                strtolower($role)
                .'-edit-'
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

    public function test_admin_can_update_clear_and_preserve_referrer_notes(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);
        $registration->update(['referrer_notes' => 'CATATAN LAMA']);
        $url = route('admin.registrations.update', [
            'registration' => $registration,
            'period_id' => $this->activePeriod->id,
        ]);
        $this->actingAs($this->makeUser('ADMIN'));

        $payload = $this->validPayload($registration);
        $payload['referrer_notes'] = 'CATATAN BARU';
        $this->put($url, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referrer_notes' => 'CATATAN BARU',
        ]);

        $payload['referrer_notes'] = null;
        $this->put($url, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referrer_notes' => null,
        ]);

        $registration->refresh()->update(['referrer_notes' => 'CATATAN DIPERTAHANKAN']);
        unset($payload['referrer_notes']);
        $this->put($url, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referrer_notes' => 'CATATAN DIPERTAHANKAN',
        ]);
    }

    public function test_edit_form_respects_period_visibility_settings(): void
    {
        $registration = $this->makeRegistration($this->activePeriod);
        $admin = $this->makeUser('ADMIN');
        $url = route('admin.registrations.edit', [
            'registration' => $registration,
            'period_id' => $this->activePeriod->id,
        ]);

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

    public function test_registration_detail_displays_historical_referrer_snapshot(): void
    {
        $registration = $this->makeRegistration($this->historicalPeriod);
        $registration->update([
            'graduation_year' => 2025,
            'referrer_name' => 'PAK AHMAD HISTORIS',
            'referrer_source' => 'JARINGAN LAMA',
            'referral_source_id' => null,
        ]);

        $this->actingAs($this->makeUser('ADMIN'))
            ->get(route('admin.registrations.show', [
                'registration' => $registration,
                'period_id' => $this->historicalPeriod->id,
            ]))
            ->assertOk()
            ->assertSee('Informasi Pembawa')
            ->assertSee('PAK AHMAD HISTORIS')
            ->assertSee('JARINGAN LAMA')
            ->assertSee('Tahun Lulus')
            ->assertSee('2025');
    }

    public function test_registration_detail_displays_prospective_student_referrer(): void
    {
        $candidate = $this->makeRegistration($this->activePeriod);
        $registration = $this->makeRegistration($this->activePeriod);
        $source = \App\Models\ReferralSource::query()->create([
            'name' => 'CALON SISWA',
            'code' => 'PROSPECTIVE_STUDENT',
            'is_active' => true,
        ]);

        $registration->update([
            'referrer_name' => 'PEMBAWA TEST',
            'referrer_source' => 'CALON SISWA',
            'referral_source_id' => $source->id,
            'referrer_registration_id' => $candidate->id,
            'referrer_notes' => 'DIREKOMENDASIKAN TEMAN',
        ]);

        $this->actingAs($this->makeUser('ADMIN'))
            ->get(route('admin.registrations.show', [
                'registration' => $registration,
                'period_id' => $this->activePeriod->id,
            ]))
            ->assertOk()
            ->assertSee('Informasi Pembawa')
            ->assertSee('PEMBAWA TEST')
            ->assertSee('CALON SISWA')
            ->assertSee($candidate->registration_number)
            ->assertSee($candidate->full_name)
            ->assertSee('DIREKOMENDASIKAN TEMAN');
    }

    private function makeRegistration(
        PpdbPeriod $period
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::query()->create([
            'period_id' => $period->id,
            'admission_path_id' => $this->activePath->id,
            'major_id' => $this->major->id,

            'registration_number' =>
                'EDIT-'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'nik' =>
                '3377777777'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'nisn' => null,
            'full_name' => 'EDIT REGISTRATION TEST '.$sequence,
            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',
            'origin_school' => 'SMP EDIT TEST',
            'hamlet' => 'KRAJAN',
            'rt' => '001',
            'rw' => '002',
            'village' => 'TEST VILLAGE',
            'district' => 'TEST DISTRICT',
            'city' => 'KEBUMEN',
            'province' => 'JAWA TENGAH',
            'postal_code' => '54311',
            'father_name' => 'AYAH TEST',
            'mother_name' => 'IBU TEST',
            'father_job' => 'WIRASWASTA',
            'mother_job' => 'IBU RUMAH TANGGA',
            'whatsapp' => '08127777'.str_pad(
                (string) $sequence,
                4,
                '0',
                STR_PAD_LEFT
            ),
            'graduation_score' => 85.50,
            'referrer_name' => null,
            'referrer_source' => null,
            'data_source' => 'ADMIN',
            'status' => 'REGISTERED',
            'registered_at' => now(),
            'notes' => 'Catatan awal.',
        ]);
    }

    private function validPayload(
        Registration $registration
    ): array {
        return [
            'nik' => $registration->nik,
            'nisn' => $registration->nisn,
            'full_name' => $registration->full_name,
            'birth_place' => $registration->birth_place,
            'birth_date' =>
                $registration->birth_date?->format('Y-m-d'),
            'gender' => $registration->gender,
            'religion' => $registration->religion,
            'origin_school' => $registration->origin_school,
            'hamlet' => $registration->hamlet,
            'rt' => $registration->rt,
            'rw' => $registration->rw,
            'village' => $registration->village,
            'district' => $registration->district,
            'city' => $registration->city,
            'province' => $registration->province,
            'postal_code' => $registration->postal_code,
            'father_name' => $registration->father_name,
            'mother_name' => $registration->mother_name,
            'father_job' => $registration->father_job,
            'mother_job' => $registration->mother_job,
            'whatsapp' => $registration->whatsapp,
            'graduation_score' => $registration->graduation_score,
            'admission_path_id' =>
                $registration->admission_path_id,
            'major_id' => $registration->major_id,
            'referrer_name' => $registration->referrer_name,
            'referrer_source' => $registration->referrer_source,
            'notes' => $registration->notes,


            'special_programs' => [],
        ];
    }
}