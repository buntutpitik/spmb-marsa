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

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_is_paginated_twenty_items_per_page(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        for ($i = 1; $i <= 21; $i++) {
            ActivityLog::query()->create([
                'user_id' => $superadmin->id,
                'registration_id' => null,
                'action' => 'PAGINATION_TEST',
                'description' => sprintf(
                    'Aktivitas pagination %02d',
                    $i
                ),
                'created_at' => now()
                    ->addSeconds($i),
            ]);
        }

        /*
        * Page 1 berisi 20 log terbaru:
        * 21 sampai 02.
        *
        * Log 01 harus berada di page 2.
        */
        $this->actingAs($superadmin)
            ->get(
                route('admin.activity-logs.index')
            )
            ->assertOk()
            ->assertSee('Aktivitas pagination 21')
            ->assertSee('Aktivitas pagination 02')
            ->assertDontSee('Aktivitas pagination 01');

        /*
        * Page 2 hanya berisi log paling lama.
        */
        $this->actingAs($superadmin)
            ->get(
                route(
                    'admin.activity-logs.index',
                    [
                        'page' => 2,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Aktivitas pagination 01')
            ->assertDontSee('Aktivitas pagination 21');
    }

    public function test_guest_cannot_access_activity_log(): void
    {
        $this->get('/admin/activity-logs')
            ->assertRedirect(route('login'));
    }

    public function test_superadmin_can_access_activity_log(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        $this->actingAs($superadmin)
            ->get('/admin/activity-logs')
            ->assertOk();
    }

    public function test_admin_cannot_access_activity_log(): void
    {
        $this->actingAs(
            $this->makeUser('ADMIN')
        )
            ->get('/admin/activity-logs')
            ->assertForbidden();
    }

    public function test_panitia_cannot_access_activity_log(): void
    {
        $this->actingAs(
            $this->makeUser('PANITIA')
        )
            ->get('/admin/activity-logs')
            ->assertForbidden();
    }

    public function test_bendahara_cannot_access_activity_log(): void
    {
        $this->actingAs(
            $this->makeUser('BENDAHARA')
        )
            ->get('/admin/activity-logs')
            ->assertForbidden();
    }

    public function test_activity_logs_are_shown_newest_first(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        ActivityLog::query()->create([
            'user_id' => $superadmin->id,
            'registration_id' => null,
            'action' => 'FIRST_ACTION',
            'description' => 'Aktivitas paling lama',
            'created_at' => now()->subMinutes(10),
        ]);

        ActivityLog::query()->create([
            'user_id' => $superadmin->id,
            'registration_id' => null,
            'action' => 'LAST_ACTION',
            'description' => 'Aktivitas paling baru',
            'created_at' => now(),
        ]);

        $response = $this
            ->actingAs($superadmin)
            ->get('/admin/activity-logs');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Aktivitas paling baru',
                'Aktivitas paling lama',
            ]);
    }

    public function test_activity_log_can_filter_action(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        ActivityLog::query()->create([
            'user_id' => $superadmin->id,
            'registration_id' => null,
            'action' => 'CREATE_USER',
            'description' => 'User baru dibuat.',
            'created_at' => now(),
        ]);

        ActivityLog::query()->create([
            'user_id' => $superadmin->id,
            'registration_id' => null,
            'action' => 'CHANGE_STATUS',
            'description' => 'Status pendaftaran diubah.',
            'created_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/activity-logs?action=CREATE_USER')
            ->assertOk()
            ->assertSee('User baru dibuat.')
            ->assertDontSee('Status pendaftaran diubah.');
    }

    public function test_activity_log_can_filter_actor(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');
        $admin = $this->makeUser('ADMIN');
        $panitia = $this->makeUser('PANITIA');

        ActivityLog::query()->create([
            'user_id' => $admin->id,
            'registration_id' => null,
            'action' => 'TEST_ACTION',
            'description' => 'Aktivitas oleh admin.',
            'created_at' => now(),
        ]);

        ActivityLog::query()->create([
            'user_id' => $panitia->id,
            'registration_id' => null,
            'action' => 'TEST_ACTION',
            'description' => 'Aktivitas oleh panitia.',
            'created_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/activity-logs?user_id='.$admin->id)
            ->assertOk()
            ->assertSee('Aktivitas oleh admin.')
            ->assertDontSee('Aktivitas oleh panitia.');
    }

    public function test_activity_log_can_search_description(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        ActivityLog::query()->create([
            'user_id' => $superadmin->id,
            'registration_id' => null,
            'action' => 'TEST_ACTION',
            'description' => 'Reset password akun bendahara.',
            'created_at' => now(),
        ]);

        ActivityLog::query()->create([
            'user_id' => $superadmin->id,
            'registration_id' => null,
            'action' => 'TEST_ACTION',
            'description' => 'Aktivitas lain.',
            'created_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/activity-logs?q=bendahara')
            ->assertOk()
            ->assertSee('Reset password akun bendahara.')
            ->assertDontSee('Aktivitas lain.');
    }

    public function test_activity_log_can_search_registration_name(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        $registration = $this->makeRegistration(
            'CALON SISWA KHUSUS'
        );

        ActivityLog::query()->create([
            'user_id' => $superadmin->id,
            'registration_id' => $registration->id,
            'action' => 'CREATE_REGISTRATION',
            'description' => 'Pendaftaran dibuat.',
            'created_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/activity-logs?q=CALON SISWA KHUSUS')
            ->assertOk()
            ->assertSee('Pendaftaran dibuat.');
    }

    public function test_activity_log_can_search_registration_number(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        $registration = $this->makeRegistration(
            'CALON SISWA NOMOR'
        );

        ActivityLog::query()->create([
            'user_id' => $superadmin->id,
            'registration_id' => $registration->id,
            'action' => 'CREATE_REGISTRATION',
            'description' => 'Pendaftaran nomor ditemukan.',
            'created_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get(
                '/admin/activity-logs?q='
                .$registration->registration_number
            )
            ->assertOk()
            ->assertSee('Pendaftaran nomor ditemukan.');
    }

    public function test_activity_log_without_registration_is_still_visible(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        ActivityLog::query()->create([
            'user_id' => $superadmin->id,
            'registration_id' => null,
            'action' => 'CREATE_USER',
            'description' => 'User baru tanpa registration.',
            'metadata' => [
                'target_user_id' => 999,
                'target_name' => 'USER TARGET',
            ],
            'created_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/activity-logs')
            ->assertOk()
            ->assertSee('User baru tanpa registration.')
            ->assertSee('USER TARGET');
    }

    public function test_activity_log_displays_actor_name(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');
        $admin = $this->makeUser('ADMIN');

        ActivityLog::query()->create([
            'user_id' => $admin->id,
            'registration_id' => null,
            'action' => 'TEST_ACTION',
            'description' => 'Aktivitas actor test.',
            'created_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/activity-logs')
            ->assertOk()
            ->assertSee($admin->name);
    }

    private function makeUser(
        string $role
    ): User {
        static $sequence = 0;

        $sequence++;

        return User::factory()->create([
            'name' => $role.' ACTIVITY TEST',
            'email' => strtolower($role)
                .'.activity.'
                .$sequence
                .'@example.test',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function makeRegistration(
        string $name
    ): Registration {
        static $sequence = 0;

        $sequence++;

        $school = School::query()->create([
            'name' => 'SMK ACTIVITY TEST '.$sequence,
            'npsn' => str_pad(
                (string) (97000000 + $sequence),
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
            'number_prefix' => 'ACT',
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
            'code' => 'AC'.$sequence,
            'name' => 'JURUSAN ACTIVITY '.$sequence,
            'short_name' => 'AC'.$sequence,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return Registration::query()->create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,

            'registration_number' =>
                'ACT-'
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
            'full_name' => $name,
            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',
            'origin_school' => 'SMP ACTIVITY TEST',

            'whatsapp' =>
                '08127777'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'data_source' => 'ADMIN',
            'status' => 'REGISTERED',
            'created_by' => null,
            'registered_at' => now(),
            'notes' => null,
        ]);
    }
}