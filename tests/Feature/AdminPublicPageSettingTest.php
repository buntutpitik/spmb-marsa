<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use App\Models\PublicPageSetting;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPublicPageSettingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PpdbPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK PUBLIC PAGE TEST',
            'npsn' => '12345678',
            'address' => 'Jl. Test No. 9',
            'phone' => '0287123456',
            'whatsapp' => '081234567890',
            'email' => 'info@example.test',
            'website' => 'https://example.test',
        ]);

        $this->period = PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);
    }

    public function test_guest_cannot_access_public_page_setting(): void
    {
        $this->get(
            route('admin.public-page.edit')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_superadmin_can_access_public_page_setting(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->get(
                route('admin.public-page.edit')
            )
            ->assertOk()
            ->assertSee('Halaman Publik')
            ->assertSee($this->school->name)
            ->assertSee('Simpan Halaman Publik');
    }

    public function test_non_superadmin_roles_cannot_access_public_page_setting(): void
    {
        foreach (
            [
                'ADMIN',
                'PANITIA',
                'BENDAHARA',
            ] as $role
        ) {
            $user = $this->makeUser($role);

            $this->actingAs($user)
                ->get(
                    route('admin.public-page.edit')
                )
                ->assertForbidden();
        }
    }

    public function test_non_superadmin_roles_cannot_update_public_page_setting(): void
    {
        foreach (
            [
                'ADMIN',
                'PANITIA',
                'BENDAHARA',
            ] as $role
        ) {
            $user = $this->makeUser($role);

            $this->actingAs($user)
                ->put(
                    route('admin.public-page.update'),
                    $this->validPayload()
                )
                ->assertForbidden();
        }

        $this->assertDatabaseCount(
            'public_page_settings',
            0
        );
    }

    public function test_superadmin_can_create_public_page_setting(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $response = $this->actingAs($user)
            ->put(
                route('admin.public-page.update'),
                $this->validPayload()
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('admin.public-page.edit')
            );

        $this->assertDatabaseHas(
            'public_page_settings',
            [
                'school_id' => $this->school->id,
                'hero_title' =>
                    'SPMB SMK PUBLIC PAGE TEST',
                'hero_subtitle' =>
                    'Penerimaan Murid Baru 2027/2028',
                'announcement_title' =>
                    'Pendaftaran Telah Dibuka',
                'show_announcement' => 1,
                'show_requirements' => 1,
                'show_registration_steps' => 1,
                'show_reenrollment_information' => 1,
                'show_contact' => 1,
            ]
        );

        $this->assertDatabaseCount(
            'public_page_settings',
            1
        );
    }

    public function test_updating_existing_setting_does_not_create_duplicate_row(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $setting = PublicPageSetting::query()->create([
            'school_id' => $this->school->id,
            'hero_title' => 'Judul Lama',
            'show_announcement' => true,
            'show_requirements' => true,
            'show_registration_steps' => true,
            'show_reenrollment_information' => true,
            'show_contact' => true,
        ]);

        $this->actingAs($user)
            ->put(
                route('admin.public-page.update'),
                $this->validPayload([
                    'hero_title' => 'Judul Baru',
                ])
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount(
            'public_page_settings',
            1
        );

        $setting->refresh();

        $this->assertSame(
            'Judul Baru',
            $setting->hero_title
        );
    }

    public function test_unchecked_visibility_fields_are_stored_as_false(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $payload = $this->validPayload();

        unset(
            $payload['show_announcement'],
            $payload['show_requirements'],
            $payload['show_registration_steps'],
            $payload['show_reenrollment_information'],
            $payload['show_contact']
        );

        $this->actingAs($user)
            ->put(
                route('admin.public-page.update'),
                $payload
            )
            ->assertSessionHasNoErrors();

        $setting = PublicPageSetting::query()
            ->where(
                'school_id',
                $this->school->id
            )
            ->firstOrFail();

        $this->assertFalse(
            $setting->show_announcement
        );

        $this->assertFalse(
            $setting->show_requirements
        );

        $this->assertFalse(
            $setting->show_registration_steps
        );

        $this->assertFalse(
            $setting->show_reenrollment_information
        );

        $this->assertFalse(
            $setting->show_contact
        );
    }

    public function test_text_fields_are_trimmed_and_empty_values_become_null(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route('admin.public-page.update'),
                $this->validPayload([
                    'hero_title' =>
                        '   Judul Bersih   ',

                    'hero_subtitle' =>
                        '   ',

                    'announcement_title' =>
                        '  Pengumuman Baru  ',
                ])
            )
            ->assertSessionHasNoErrors();

        $setting = PublicPageSetting::query()
            ->where(
                'school_id',
                $this->school->id
            )
            ->firstOrFail();

        $this->assertSame(
            'Judul Bersih',
            $setting->hero_title
        );

        $this->assertNull(
            $setting->hero_subtitle
        );

        $this->assertSame(
            'Pengumuman Baru',
            $setting->announcement_title
        );
    }

    public function test_oversized_hero_title_is_rejected(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->from(
                route('admin.public-page.edit')
            )
            ->put(
                route('admin.public-page.update'),
                $this->validPayload([
                    'hero_title' =>
                        str_repeat('A', 201),
                ])
            )
            ->assertRedirect(
                route('admin.public-page.edit')
            )
            ->assertSessionHasErrors(
                'hero_title'
            );

        $this->assertDatabaseCount(
            'public_page_settings',
            0
        );
    }

    public function test_active_period_school_is_used(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $otherSchool = School::query()->create([
            'name' => 'SEKOLAH HISTORIS',
        ]);

        PpdbPeriod::query()->create([
            'school_id' => $otherSchool->id,
            'name' => '2026/2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'registration_open' => '2026-01-01',
            'registration_close' => '2026-06-30',
            'status' => 'CLOSED',
            'is_active' => false,
            'number_prefix' => 'OLD',
            'number_year' => 2026,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 200000,
        ]);

        $this->actingAs($user)
            ->put(
                route('admin.public-page.update'),
                $this->validPayload()
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'public_page_settings',
            [
                'school_id' => $this->school->id,
            ]
        );

        $this->assertDatabaseMissing(
            'public_page_settings',
            [
                'school_id' => $otherSchool->id,
            ]
        );
    }

    public function test_first_school_is_used_when_no_active_period_exists(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->put(
                route('admin.public-page.update'),
                $this->validPayload()
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'public_page_settings',
            [
                'school_id' => $this->school->id,
            ]
        );
    }

    public function test_update_creates_activity_log_with_audit_context(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        PublicPageSetting::query()->create([
            'school_id' => $this->school->id,
            'hero_title' => 'Judul Lama',
            'show_announcement' => true,
            'show_requirements' => true,
            'show_registration_steps' => true,
            'show_reenrollment_information' => true,
            'show_contact' => true,
        ]);

        $this->actingAs($user)
            ->withServerVariables([
                'REMOTE_ADDR' =>
                    '203.0.113.60',

                'HTTP_USER_AGENT' =>
                    'SPMB-MARSA-Public-Page-Test/1.0',
            ])
            ->put(
                route('admin.public-page.update'),
                $this->validPayload([
                    'hero_title' =>
                        'Judul Baru',
                ])
            )
            ->assertSessionHasNoErrors();

        $log = ActivityLog::query()
            ->where(
                'action',
                'UPDATE_PUBLIC_PAGE_SETTING'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $user->id,
            $log->user_id
        );

        $this->assertSame(
            '203.0.113.60',
            $log->ip_address
        );

        $this->assertSame(
            'SPMB-MARSA-Public-Page-Test/1.0',
            $log->user_agent
        );

        $this->assertSame(
            $this->school->id,
            $log->metadata['school_id']
        );

        $this->assertSame(
            'Judul Lama',
            $log->metadata['old']['hero_title']
        );

        $this->assertSame(
            'Judul Baru',
            $log->metadata['new']['hero_title']
        );
    }

    public function test_settings_index_contains_public_page_setting_link(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->get(
                route('admin.settings.index')
            )
            ->assertOk()
            ->assertSee('Halaman Publik')
            ->assertSee('Kelola Halaman Publik')
            ->assertSee(
                route('admin.public-page.edit'),
                false
            );
    }

    private function validPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'hero_title' =>
                    'SPMB SMK PUBLIC PAGE TEST',

                'hero_subtitle' =>
                    'Penerimaan Murid Baru 2027/2028',

                'hero_description' =>
                    'Bergabung dan berkembang bersama kami.',

                'announcement_title' =>
                    'Pendaftaran Telah Dibuka',

                'announcement_body' =>
                    'Pendaftaran peserta didik baru telah dibuka.',

                'show_announcement' => '1',

                'requirements' =>
                    "Siapkan NIK\nSiapkan NISN",

                'show_requirements' => '1',

                'registration_steps' =>
                    "Isi formulir\nPeriksa data\nKirim pendaftaran\nCetak kartu",

                'show_registration_steps' => '1',

                'reenrollment_information' =>
                    'Informasi daftar ulang calon siswa.',

                'show_reenrollment_information' => '1',

                'show_contact' => '1',
            ],
            $overrides
        );
    }

    private function makeUser(
        string $role
    ): User {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }
}