<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\OriginSchool;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\ReliefOption;
use App\Models\School;
use App\Models\SpecialProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    public function test_all_internal_roles_can_access_dashboard(): void
    {
        foreach ($this->internalRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/')
                ->assertOk();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Operasional umum
    |--------------------------------------------------------------------------
    */

    public function test_all_internal_roles_can_access_registration_list(): void
    {
        foreach ($this->internalRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/pendaftaran')
                ->assertOk();
        }
    }

    public function test_all_internal_roles_can_access_admission_page(): void
    {
        foreach ($this->internalRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/penerimaan')
                ->assertOk();
        }
    }

    public function test_all_internal_roles_can_access_reenrollment_page(): void
    {
        foreach ($this->internalRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/daftar-ulang')
                ->assertOk();
        }
    }

    public function test_all_internal_roles_can_access_general_recap(): void
    {
        foreach ($this->internalRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/rekap')
                ->assertOk();
        }
    }

    public function test_all_internal_roles_can_access_origin_school_recap(): void
    {
        foreach ($this->internalRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/rekap/asal-sekolah')
                ->assertOk();
        }
    }

    public function test_all_internal_roles_can_access_referral_recap(): void
    {
        foreach ($this->internalRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/rekap/referral')
                ->assertOk();
        }
    }

    public function test_all_internal_roles_can_access_analytics(): void
    {
        foreach ($this->internalRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/analitik')
                ->assertOk();
        }
    }

    public function test_all_internal_roles_can_access_reports_page(): void
    {
        foreach ($this->internalRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/laporan')
                ->assertOk();
        }
    }

    public function test_all_internal_roles_can_access_whatsapp_log(): void
    {
        foreach ($this->internalRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/whatsapp')
                ->assertOk();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Keuangan
    |--------------------------------------------------------------------------
    */

    public function test_superadmin_admin_and_bendahara_can_access_reenrollment_finance(): void
    {
        foreach ($this->financeRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/rekap/keuangan-daftar-ulang')
                ->assertOk();
        }
    }

    public function test_panitia_cannot_access_reenrollment_finance(): void
    {
        $this->actingAs(
            $this->makeUser('PANITIA')
        )
            ->get('/admin/rekap/keuangan-daftar-ulang')
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Pengaturan
    |--------------------------------------------------------------------------
    */

    public function test_superadmin_can_access_settings(): void
    {
        $this->actingAs(
            $this->makeUser('SUPERADMIN')
        )
            ->get('/admin/pengaturan')
            ->assertOk();
    }

    public function test_non_superadmin_roles_cannot_access_settings(): void
    {
        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/pengaturan')
                ->assertForbidden();
        }
    }

    public function test_superadmin_can_access_relief_option_settings(): void
    {
        $this->actingAs(
            $this->makeUser('SUPERADMIN')
        )
            ->get('/admin/pengaturan/keringanan')
            ->assertOk();
    }

    public function test_non_superadmin_roles_cannot_access_relief_option_settings(): void
    {
        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/pengaturan/keringanan')
                ->assertForbidden();
        }
    }

    public function test_superadmin_can_access_special_program_settings(): void
    {
        $this->actingAs(
            $this->makeUser('SUPERADMIN')
        )
            ->get('/admin/pengaturan/program-khusus')
            ->assertOk();
    }

    public function test_non_superadmin_roles_cannot_access_special_program_settings(): void
    {
        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/pengaturan/program-khusus')
                ->assertForbidden();
        }
    }

    public function test_superadmin_can_access_origin_school_settings(): void
    {
        $this->actingAs(
            $this->makeUser('SUPERADMIN')
        )
            ->get('/admin/pengaturan/asal-sekolah')
            ->assertOk();
    }

    public function test_non_superadmin_roles_cannot_access_origin_school_settings(): void
    {
        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->get('/admin/pengaturan/asal-sekolah')
                ->assertForbidden();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Inactive account
    |--------------------------------------------------------------------------
    */

    public function test_inactive_internal_user_cannot_keep_admin_access(): void
    {
        $user = User::factory()->create([
            'role' => 'PANITIA',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get('/admin/pendaftaran')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | Positive write: perubahan status
    |--------------------------------------------------------------------------
    */

    public function test_panitia_can_change_registration_status(): void
    {
        $panitia = $this->makeUser('PANITIA');

        $fixture = $this->makeRegistrationFixture(
            'REGISTERED',
            $panitia
        );

        $registration = $fixture['registration'];

        $this->actingAs($panitia)
            ->patch(
                route(
                    'admin.registrations.status.update',
                    $registration
                ),
                [
                    'status' => 'ACCEPTED',
                    'notes' => 'Diterima oleh Panitia.',
                ]
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.registrations.show',
                    $registration
                )
            );

        $registration->refresh();

        $this->assertSame(
            'ACCEPTED',
            $registration->status
        );

        $this->assertNotNull(
            $registration->accepted_at
        );

        $this->assertDatabaseHas(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'from_status' => 'REGISTERED',
                'to_status' => 'ACCEPTED',
                'changed_by' => $panitia->id,
            ]
        );
    }

    public function test_bendahara_can_change_registration_status(): void
    {
        $bendahara = $this->makeUser('BENDAHARA');

        $fixture = $this->makeRegistrationFixture(
            'REGISTERED',
            $bendahara
        );

        $registration = $fixture['registration'];

        $this->actingAs($bendahara)
            ->patch(
                route(
                    'admin.registrations.status.update',
                    $registration
                ),
                [
                    'status' => 'ACCEPTED',
                    'notes' => 'Diterima oleh Bendahara.',
                ]
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.registrations.show',
                    $registration
                )
            );

        $registration->refresh();

        $this->assertSame(
            'ACCEPTED',
            $registration->status
        );

        $this->assertDatabaseHas(
            'registration_status_histories',
            [
                'registration_id' => $registration->id,
                'from_status' => 'REGISTERED',
                'to_status' => 'ACCEPTED',
                'changed_by' => $bendahara->id,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Positive / negative write: pembayaran
    |--------------------------------------------------------------------------
    */

    public function test_bendahara_can_store_reenrollment_payment(): void
    {
        $bendahara = $this->makeUser('BENDAHARA');

        $fixture = $this->makeRegistrationFixture(
            'ACCEPTED',
            $bendahara
        );

        $registration = $fixture['registration'];

        $this->actingAs($bendahara)
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    $registration
                ),
                [
                    'amount' => 100000,
                    'payment_method' => 'CASH',
                    'reference_number' => null,
                    'notes' => 'Pembayaran oleh bendahara.',
                ]
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.registrations.show',
                    $registration
                )
            );

        $this->assertDatabaseHas(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
                'amount' => 100000,
                'payment_method' => 'CASH',
                'received_by' => $bendahara->id,
                'notes' => 'Pembayaran oleh bendahara.',
            ]
        );
    }

    public function test_panitia_cannot_store_reenrollment_payment(): void
    {
        $panitia = $this->makeUser('PANITIA');

        $fixture = $this->makeRegistrationFixture(
            'ACCEPTED',
            $panitia
        );

        $registration = $fixture['registration'];

        $this->actingAs($panitia)
            ->post(
                route(
                    'admin.registrations.reenrollment-payments.store',
                    $registration
                ),
                [
                    'amount' => 100000,
                    'payment_method' => 'CASH',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'reenrollment_payments',
            [
                'registration_id' => $registration->id,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Write protection: Keringanan
    |--------------------------------------------------------------------------
    */

    public function test_non_superadmin_roles_cannot_store_relief_option(): void
    {
        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->post(
                    route('admin.relief-options.store'),
                    [
                        'name' => 'KERINGANAN '.$role,
                        'sort_order' => 1,
                    ]
                )
                ->assertForbidden();
        }
    }

    public function test_non_superadmin_roles_cannot_update_relief_option(): void
    {
        $reliefOption = ReliefOption::query()->create([
            'name' => 'KERINGANAN RBAC',
            'slug' => 'keringanan-rbac',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->put(
                    route(
                        'admin.relief-options.update',
                        $reliefOption
                    ),
                    [
                        'name' => 'UBAH '.$role,
                        'sort_order' => 99,
                    ]
                )
                ->assertForbidden();
        }

        $reliefOption->refresh();

        $this->assertSame(
            'KERINGANAN RBAC',
            $reliefOption->name
        );
    }

    public function test_non_superadmin_roles_cannot_toggle_relief_option_master(): void
    {
        $reliefOption = ReliefOption::query()->create([
            'name' => 'KERINGANAN MASTER RBAC',
            'slug' => 'keringanan-master-rbac',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->patch(
                    route(
                        'admin.relief-options.toggle-master',
                        $reliefOption
                    )
                )
                ->assertForbidden();
        }

        $reliefOption->refresh();

        $this->assertTrue(
            $reliefOption->is_active
        );
    }

    public function test_non_superadmin_roles_cannot_toggle_relief_option_period(): void
    {
        $reliefOption = ReliefOption::query()->create([
            'name' => 'KERINGANAN PERIODE RBAC',
            'slug' => 'keringanan-periode-rbac',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->patch(
                    route(
                        'admin.relief-options.toggle-period',
                        $reliefOption
                    )
                )
                ->assertForbidden();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Write protection: Program Khusus
    |--------------------------------------------------------------------------
    */

    public function test_non_superadmin_roles_cannot_store_special_program(): void
    {
        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->post(
                    route('admin.special-programs.store'),
                    [
                        'name' => 'PROGRAM '.$role,
                        'sort_order' => 1,
                    ]
                )
                ->assertForbidden();
        }
    }

    public function test_non_superadmin_roles_cannot_update_special_program(): void
    {
        $program = SpecialProgram::query()->create([
            'name' => 'PROGRAM RBAC',
            'slug' => 'program-rbac',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->put(
                    route(
                        'admin.special-programs.update',
                        $program
                    ),
                    [
                        'name' => 'UBAH '.$role,
                        'sort_order' => 99,
                    ]
                )
                ->assertForbidden();
        }

        $program->refresh();

        $this->assertSame(
            'PROGRAM RBAC',
            $program->name
        );
    }

    public function test_non_superadmin_roles_cannot_toggle_special_program_master(): void
    {
        $program = SpecialProgram::query()->create([
            'name' => 'PROGRAM MASTER RBAC',
            'slug' => 'program-master-rbac',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->patch(
                    route(
                        'admin.special-programs.toggle-master',
                        $program
                    )
                )
                ->assertForbidden();
        }

        $program->refresh();

        $this->assertTrue(
            $program->is_active
        );
    }

    public function test_non_superadmin_roles_cannot_toggle_special_program_period(): void
    {
        $program = SpecialProgram::query()->create([
            'name' => 'PROGRAM PERIODE RBAC',
            'slug' => 'program-periode-rbac',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->patch(
                    route(
                        'admin.special-programs.toggle-period',
                        $program
                    )
                )
                ->assertForbidden();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Write protection: Asal Sekolah
    |--------------------------------------------------------------------------
    */

    public function test_non_superadmin_roles_cannot_store_origin_school(): void
    {
        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->post(
                    route('admin.origin-schools.store'),
                    [
                        'name' => 'SMP RBAC '.$role,
                        'type' => 'SMP',
                        'sort_order' => 1,
                    ]
                )
                ->assertForbidden();
        }
    }

    public function test_non_superadmin_roles_cannot_update_origin_school(): void
    {
        $school = OriginSchool::query()->create([
            'name' => 'SMP ORIGIN RBAC',
            'type' => 'SMP',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->put(
                    route(
                        'admin.origin-schools.update',
                        $school
                    ),
                    [
                        'name' => 'SMP UBAH '.$role,
                        'type' => 'SMP',
                        'sort_order' => 99,
                    ]
                )
                ->assertForbidden();
        }

        $school->refresh();

        $this->assertSame(
            'SMP ORIGIN RBAC',
            $school->name
        );
    }

    public function test_non_superadmin_roles_cannot_toggle_origin_school(): void
    {
        $school = OriginSchool::query()->create([
            'name' => 'SMP TOGGLE RBAC',
            'type' => 'SMP',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach ($this->nonSuperadminRoles() as $role) {
            $this->actingAs(
                $this->makeUser($role)
            )
                ->patch(
                    route(
                        'admin.origin-schools.toggle',
                        $school
                    )
                )
                ->assertForbidden();

            $school->refresh();

            $this->assertTrue(
                $school->is_active
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function internalRoles(): array
    {
        return [
            'SUPERADMIN',
            'ADMIN',
            'PANITIA',
            'BENDAHARA',
        ];
    }

    private function financeRoles(): array
    {
        return [
            'SUPERADMIN',
            'ADMIN',
            'BENDAHARA',
        ];
    }

    private function nonSuperadminRoles(): array
    {
        return [
            'ADMIN',
            'PANITIA',
            'BENDAHARA',
        ];
    }

    private function makeUser(string $role): User
    {
        static $sequence = 0;

        $sequence++;

        return User::factory()->create([
            'name' => $role.' ACCESS TEST',
            'email' => strtolower($role)
                .'.access.'
                .$sequence
                .'@example.test',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function makeRegistrationFixture(
        string $status,
        User $creator
    ): array {
        static $sequence = 0;

        $sequence++;

        $school = School::query()->create([
            'name' => 'SMK RBAC TEST '.$sequence,
            'npsn' => str_pad(
                (string) (88000000 + $sequence),
                8,
                '0',
                STR_PAD_LEFT
            ),
        ]);

        $period = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'RBAC',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        $path = AdmissionPath::query()->create([
            'period_id' => $period->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $major = Major::query()->create([
            'school_id' => $school->id,
            'code' => 'RB'.$sequence,
            'name' => 'JURUSAN RBAC '.$sequence,
            'short_name' => 'RB'.$sequence,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $registration = Registration::query()->create([
            'period_id' => $period->id,
            'wave_id' => null,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,

            'registration_number' =>
                'RBAC-'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'nik' =>
                '3388888888'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'nisn' => null,

            'full_name' =>
                'PENDAFTAR RBAC '.$sequence,

            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',

            'origin_school' => 'SMP RBAC TEST',

            'whatsapp' =>
                '08128888'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'data_source' => 'ADMIN',
            'status' => $status,
            'created_by' => $creator->id,
            'registered_at' => now(),

            'accepted_at' =>
                in_array(
                    $status,
                    [
                        'ACCEPTED',
                        'REENROLLED',
                    ],
                    true
                )
                    ? now()
                    : null,

            'rejected_at' =>
                $status === 'REJECTED'
                    ? now()
                    : null,

            'reenrolled_at' =>
                $status === 'REENROLLED'
                    ? now()
                    : null,

            'withdrawn_at' =>
                $status === 'WITHDRAWN'
                    ? now()
                    : null,

            'notes' => null,
        ]);

        return [
            'school' => $school,
            'period' => $period,
            'path' => $path,
            'major' => $major,
            'registration' => $registration,
        ];
    }
}