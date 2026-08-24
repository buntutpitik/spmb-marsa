<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarRoleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_sees_settings_menu(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Pengaturan');
    }

    public function test_admin_does_not_see_settings_menu(): void
    {
        $user = $this->makeUser('ADMIN');

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertDontSee('Pengaturan');
    }

    public function test_panitia_does_not_see_settings_menu(): void
    {
        $user = $this->makeUser('PANITIA');

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertDontSee('Pengaturan');
    }

    public function test_bendahara_does_not_see_settings_menu(): void
    {
        $user = $this->makeUser('BENDAHARA');

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertDontSee('Pengaturan');
    }

    public function test_all_internal_roles_see_common_operational_menu(): void
    {
        foreach ($this->internalRoles() as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)
                ->get('/')
                ->assertOk()
                ->assertSee('Dashboard')
                ->assertSee('Pendaftaran')
                ->assertSee('Penerimaan')
                ->assertSee('Daftar Ulang')
                ->assertSee('Rekap')
                ->assertSee('Analitik')
                ->assertSee('Laporan')
                ->assertSee('WhatsApp');
        }
    }

    public function test_panitia_does_not_see_payment_input_button(): void
    {
        $panitia = $this->makeUser('PANITIA');

        $registration = $this->makeAcceptedRegistration(
            $panitia
        );

        $this->actingAs($panitia)
            ->get(
                route(
                    'admin.registrations.show',
                    $registration
                )
            )
            ->assertOk()
            ->assertDontSee('Input Pembayaran');
    }

    public function test_bendahara_sees_payment_input_button(): void
    {
        $bendahara = $this->makeUser('BENDAHARA');

        $registration = $this->makeAcceptedRegistration(
            $bendahara
        );

        $this->actingAs($bendahara)
            ->get(
                route(
                    'admin.registrations.show',
                    $registration
                )
            )
            ->assertOk()
            ->assertSee('Input Pembayaran');
    }

    public function test_admin_sees_payment_input_button(): void
    {
        $admin = $this->makeUser('ADMIN');

        $registration = $this->makeAcceptedRegistration(
            $admin
        );

        $this->actingAs($admin)
            ->get(
                route(
                    'admin.registrations.show',
                    $registration
                )
            )
            ->assertOk()
            ->assertSee('Input Pembayaran');
    }

    public function test_superadmin_sees_payment_input_button(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        $registration = $this->makeAcceptedRegistration(
            $superadmin
        );

        $this->actingAs($superadmin)
            ->get(
                route(
                    'admin.registrations.show',
                    $registration
                )
            )
            ->assertOk()
            ->assertSee('Input Pembayaran');
    }

    public function test_panitia_can_still_see_payment_history_section(): void
    {
        $panitia = $this->makeUser('PANITIA');

        $registration = $this->makeAcceptedRegistration(
            $panitia
        );

        $this->actingAs($panitia)
            ->get(
                route(
                    'admin.registrations.show',
                    $registration
                )
            )
            ->assertOk()
            ->assertSee('Pembayaran Daftar Ulang')
            ->assertSee('Riwayat transaksi pembayaran daftar ulang.');
    }

    private function internalRoles(): array
    {
        return [
            'SUPERADMIN',
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
            'name' => $role.' SIDEBAR TEST',
            'email' => strtolower($role)
                .'.sidebar.'
                .$sequence
                .'@example.test',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function makeAcceptedRegistration(
        User $creator
    ): Registration {
        static $sequence = 0;

        $sequence++;

        $school = School::query()->create([
            'name' => 'SMK SIDEBAR TEST '.$sequence,
            'npsn' => str_pad(
                (string) (99000000 + $sequence),
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
            'number_prefix' => 'SIDE',
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
            'code' => 'SD'.$sequence,
            'name' => 'JURUSAN SIDEBAR '.$sequence,
            'short_name' => 'SD'.$sequence,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return Registration::query()->create([
            'period_id' => $period->id,
            'wave_id' => null,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,

            'registration_number' =>
                'SIDE-'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'nik' =>
                '3399999999'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'nisn' => null,

            'full_name' =>
                'PENDAFTAR SIDEBAR '.$sequence,

            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',

            'origin_school' => 'SMP SIDEBAR TEST',

            'whatsapp' =>
                '08129999'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'data_source' => 'ADMIN',
            'status' => 'ACCEPTED',
            'created_by' => $creator->id,
            'registered_at' => now(),
            'accepted_at' => now(),
            'notes' => null,
        ]);
    }
}