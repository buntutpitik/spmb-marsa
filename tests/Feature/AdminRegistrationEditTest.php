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

            'relief_options' => [],
            'special_programs' => [],
        ];
    }
}