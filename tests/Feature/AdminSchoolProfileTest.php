<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSchoolProfileTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PpdbPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK PROFILE TEST',
            'npsn' => '12345678',
            'address' => 'Alamat Lama',
            'village' => 'Desa Lama',
            'district' => 'Kecamatan Lama',
            'city' => 'Kabupaten Lama',
            'province' => 'Jawa Tengah',
            'postal_code' => '54311',
            'phone' => '0287123456',
            'whatsapp' => '081234567890',
            'email' => 'lama@example.test',
            'website' => 'https://lama.example.test',
            'logo_path' => null,
            'favicon_path' => null,
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

    public function test_guest_cannot_access_school_profile(): void
    {
        $this->get(
            route('admin.school-profile.edit')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_superadmin_can_access_school_profile(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->get(
                route('admin.school-profile.edit')
            )
            ->assertOk()
            ->assertSee('Profil Sekolah')
            ->assertSee($this->school->name)
            ->assertSee($this->school->npsn);
    }

    public function test_non_superadmin_roles_cannot_access_school_profile(): void
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
                    route('admin.school-profile.edit')
                )
                ->assertForbidden();
        }
    }

    public function test_superadmin_can_update_school_profile(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $response = $this->actingAs($user)
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'name' =>
                        'SMK MAARIF 9 KEBUMEN BARU',

                    'address' =>
                        'Jl. Profile Baru No. 9',

                    'village' =>
                        'Kebumen',

                    'district' =>
                        'Kebumen',

                    'city' =>
                        'Kebumen',

                    'province' =>
                        'Jawa Tengah',

                    'postal_code' =>
                        '54311',

                    'phone' =>
                        '0287388888',

                    'whatsapp' =>
                        '081299998888',

                    'email' =>
                        'INFO@MARSA.TEST',

                    'website' =>
                        'https://marsa.example.test',
                ])
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('admin.school-profile.edit')
            );

        $this->assertDatabaseHas(
            'schools',
            [
                'id' => $this->school->id,
                'name' =>
                    'SMK MAARIF 9 KEBUMEN BARU',
                'address' =>
                    'Jl. Profile Baru No. 9',
                'village' =>
                    'Kebumen',
                'district' =>
                    'Kebumen',
                'city' =>
                    'Kebumen',
                'province' =>
                    'Jawa Tengah',
                'postal_code' =>
                    '54311',
                'phone' =>
                    '0287388888',
                'whatsapp' =>
                    '081299998888',
                'email' =>
                    'info@marsa.test',
                'website' =>
                    'https://marsa.example.test',
            ]
        );
    }

    public function test_non_superadmin_cannot_update_school_profile(): void
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
                    route(
                        'admin.school-profile.update'
                    ),
                    $this->validPayload([
                        'name' =>
                            'SHOULD NOT CHANGE',
                    ])
                )
                ->assertForbidden();
        }

        $this->school->refresh();

        $this->assertSame(
            'SMK PROFILE TEST',
            $this->school->name
        );
    }

    public function test_invalid_email_is_rejected(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $response = $this->actingAs($user)
            ->from(
                route('admin.school-profile.edit')
            )
            ->put(
                route(
                    'admin.school-profile.update'
                ),
                $this->validPayload([
                    'email' =>
                        'bukan-email',
                ])
            );

        $response
            ->assertRedirect(
                route('admin.school-profile.edit')
            )
            ->assertSessionHasErrors('email');
    }

    public function test_invalid_website_is_rejected(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $response = $this->actingAs($user)
            ->from(
                route('admin.school-profile.edit')
            )
            ->put(
                route(
                    'admin.school-profile.update'
                ),
                $this->validPayload([
                    'website' =>
                        'marsa tanpa url',
                ])
            );

        $response
            ->assertRedirect(
                route('admin.school-profile.edit')
            )
            ->assertSessionHasErrors(
                'website'
            );
    }

    public function test_active_period_school_is_updated(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $otherSchool = School::query()->create([
            'name' => 'SEKOLAH LAIN',
            'npsn' => '87654321',
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
                route(
                    'admin.school-profile.update'
                ),
                $this->validPayload([
                    'name' =>
                        'SEKOLAH PERIODE AKTIF',
                ])
            )
            ->assertSessionHasNoErrors();

        $this->school->refresh();
        $otherSchool->refresh();

        $this->assertSame(
            'SEKOLAH PERIODE AKTIF',
            $this->school->name
        );

        $this->assertSame(
            'SEKOLAH LAIN',
            $otherSchool->name
        );
    }

    public function test_first_school_is_used_when_no_active_open_period_exists(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->put(
                route(
                    'admin.school-profile.update'
                ),
                $this->validPayload([
                    'name' =>
                        'FALLBACK SCHOOL UPDATED',
                ])
            )
            ->assertSessionHasNoErrors();

        $this->school->refresh();

        $this->assertSame(
            'FALLBACK SCHOOL UPDATED',
            $this->school->name
        );
    }

    public function test_school_profile_update_creates_activity_log_with_audit_context(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->withServerVariables([
                'REMOTE_ADDR' =>
                    '203.0.113.50',

                'HTTP_USER_AGENT' =>
                    'SPMB-MARSA-School-Audit-Test/1.0',
            ])
            ->put(
                route(
                    'admin.school-profile.update'
                ),
                $this->validPayload([
                    'name' =>
                        'SMK PROFILE UPDATED',
                ])
            )
            ->assertSessionHasNoErrors();

        $log = ActivityLog::query()
            ->where(
                'action',
                'UPDATE_SCHOOL_PROFILE'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $user->id,
            $log->user_id
        );

        $this->assertSame(
            '203.0.113.50',
            $log->ip_address
        );

        $this->assertSame(
            'SPMB-MARSA-School-Audit-Test/1.0',
            $log->user_agent
        );

        $this->assertSame(
            $this->school->id,
            $log->metadata['school_id']
        );

        $this->assertSame(
            'SMK PROFILE TEST',
            $log->metadata['old']['name']
        );

        $this->assertSame(
            'SMK PROFILE UPDATED',
            $log->metadata['new']['name']
        );
    }

    public function test_profile_update_does_not_change_period_principal_snapshot(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->period->update([
            'principal_name' =>
                'KEPALA SEKOLAH SNAPSHOT',

            'principal_nip' =>
                '197001012000011001',
        ]);

        $this->actingAs($user)
            ->put(
                route(
                    'admin.school-profile.update'
                ),
                $this->validPayload([
                    'name' =>
                        'SMK UPDATED',
                ])
            )
            ->assertSessionHasNoErrors();

        $this->period->refresh();

        $this->assertSame(
            'KEPALA SEKOLAH SNAPSHOT',
            $this->period->principal_name
        );

        $this->assertSame(
            '197001012000011001',
            $this->period->principal_nip
        );
    }

    public function test_superadmin_can_upload_school_logo(): void
    {
        Storage::fake('public');

        $user = $this->makeUser('SUPERADMIN');

        $logo = UploadedFile::fake()->image(
            'logo-sekolah.png',
            500,
            500
        );

        $this->actingAs($user)
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'logo' => $logo,
                ])
            )
            ->assertSessionHasNoErrors();

        $this->school->refresh();

        $this->assertNotNull(
            $this->school->logo_path
        );

        Storage::disk('public')
            ->assertExists(
                $this->school->logo_path
            );

        $this->assertStringStartsWith(
            "schools/{$this->school->id}/branding/",
            $this->school->logo_path
        );
    }

    public function test_superadmin_can_upload_school_favicon(): void
    {
        Storage::fake('public');

        $user = $this->makeUser('SUPERADMIN');

        $favicon = UploadedFile::fake()->image(
            'favicon.png',
            64,
            64
        );

        $this->actingAs($user)
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'favicon' => $favicon,
                ])
            )
            ->assertSessionHasNoErrors();

        $this->school->refresh();

        $this->assertNotNull(
            $this->school->favicon_path
        );

        Storage::disk('public')
            ->assertExists(
                $this->school->favicon_path
            );

        $this->assertStringStartsWith(
            "schools/{$this->school->id}/branding/",
            $this->school->favicon_path
        );
    }

    public function test_replacing_logo_deletes_old_logo_file(): void
    {
        Storage::fake('public');

        $oldPath =
            "schools/{$this->school->id}/branding/old-logo.png";

        Storage::disk('public')->put(
            $oldPath,
            'old-logo'
        );

        $this->school->update([
            'logo_path' => $oldPath,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $newLogo = UploadedFile::fake()->image(
            'new-logo.png',
            600,
            600
        );

        $this->actingAs($user)
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'logo' => $newLogo,
                ])
            )
            ->assertSessionHasNoErrors();

        $this->school->refresh();

        Storage::disk('public')
            ->assertMissing($oldPath);

        Storage::disk('public')
            ->assertExists(
                $this->school->logo_path
            );

        $this->assertNotSame(
            $oldPath,
            $this->school->logo_path
        );
    }

    public function test_replacing_favicon_deletes_old_favicon_file(): void
    {
        Storage::fake('public');

        $oldPath =
            "schools/{$this->school->id}/branding/old-favicon.png";

        Storage::disk('public')->put(
            $oldPath,
            'old-favicon'
        );

        $this->school->update([
            'favicon_path' => $oldPath,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $newFavicon = UploadedFile::fake()->image(
            'new-favicon.png',
            64,
            64
        );

        $this->actingAs($user)
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'favicon' => $newFavicon,
                ])
            )
            ->assertSessionHasNoErrors();

        $this->school->refresh();

        Storage::disk('public')
            ->assertMissing($oldPath);

        Storage::disk('public')
            ->assertExists(
                $this->school->favicon_path
            );

        $this->assertNotSame(
            $oldPath,
            $this->school->favicon_path
        );
    }

    public function test_superadmin_can_remove_school_logo(): void
    {
        Storage::fake('public');

        $path =
            "schools/{$this->school->id}/branding/logo.png";

        Storage::disk('public')->put(
            $path,
            'logo'
        );

        $this->school->update([
            'logo_path' => $path,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'remove_logo' => '1',
                ])
            )
            ->assertSessionHasNoErrors();

        $this->school->refresh();

        $this->assertNull(
            $this->school->logo_path
        );

        Storage::disk('public')
            ->assertMissing($path);
    }

    public function test_superadmin_can_remove_school_favicon(): void
    {
        Storage::fake('public');

        $path =
            "schools/{$this->school->id}/branding/favicon.png";

        Storage::disk('public')->put(
            $path,
            'favicon'
        );

        $this->school->update([
            'favicon_path' => $path,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'remove_favicon' => '1',
                ])
            )
            ->assertSessionHasNoErrors();

        $this->school->refresh();

        $this->assertNull(
            $this->school->favicon_path
        );

        Storage::disk('public')
            ->assertMissing($path);
    }

    public function test_existing_branding_is_preserved_when_no_branding_change_is_requested(): void
    {
        Storage::fake('public');

        $logoPath =
            "schools/{$this->school->id}/branding/logo.png";

        $faviconPath =
            "schools/{$this->school->id}/branding/favicon.png";

        Storage::disk('public')->put(
            $logoPath,
            'logo'
        );

        Storage::disk('public')->put(
            $faviconPath,
            'favicon'
        );

        $this->school->update([
            'logo_path' => $logoPath,
            'favicon_path' => $faviconPath,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'name' =>
                        'SMK PROFILE TANPA UBAH BRANDING',
                ])
            )
            ->assertSessionHasNoErrors();

        $this->school->refresh();

        $this->assertSame(
            $logoPath,
            $this->school->logo_path
        );

        $this->assertSame(
            $faviconPath,
            $this->school->favicon_path
        );

        Storage::disk('public')
            ->assertExists($logoPath);

        Storage::disk('public')
            ->assertExists($faviconPath);
    }

    public function test_invalid_logo_format_is_rejected(): void
    {
        Storage::fake('public');

        $user = $this->makeUser('SUPERADMIN');

        $file = UploadedFile::fake()->create(
            'logo.svg',
            10,
            'image/svg+xml'
        );

        $this->actingAs($user)
            ->from(
                route('admin.school-profile.edit')
            )
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'logo' => $file,
                ])
            )
            ->assertRedirect(
                route('admin.school-profile.edit')
            )
            ->assertSessionHasErrors('logo');

        $this->school->refresh();

        $this->assertNull(
            $this->school->logo_path
        );
    }

    public function test_oversized_logo_is_rejected(): void
    {
        Storage::fake('public');

        $user = $this->makeUser('SUPERADMIN');

        $file = UploadedFile::fake()->create(
            'logo.png',
            2049,
            'image/png'
        );

        $this->actingAs($user)
            ->from(
                route('admin.school-profile.edit')
            )
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'logo' => $file,
                ])
            )
            ->assertRedirect(
                route('admin.school-profile.edit')
            )
            ->assertSessionHasErrors('logo');
    }

    public function test_oversized_favicon_is_rejected(): void
    {
        Storage::fake('public');

        $user = $this->makeUser('SUPERADMIN');

        $file = UploadedFile::fake()->create(
            'favicon.png',
            513,
            'image/png'
        );

        $this->actingAs($user)
            ->from(
                route('admin.school-profile.edit')
            )
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'favicon' => $file,
                ])
            )
            ->assertRedirect(
                route('admin.school-profile.edit')
            )
            ->assertSessionHasErrors('favicon');
    }

    public function test_branding_paths_are_recorded_in_activity_log(): void
    {
        Storage::fake('public');

        $user = $this->makeUser('SUPERADMIN');

        $logo = UploadedFile::fake()->image(
            'logo.png',
            500,
            500
        );

        $favicon = UploadedFile::fake()->image(
            'favicon.png',
            64,
            64
        );

        $this->actingAs($user)
            ->put(
                route('admin.school-profile.update'),
                $this->validPayload([
                    'logo' => $logo,
                    'favicon' => $favicon,
                ])
            )
            ->assertSessionHasNoErrors();

        $this->school->refresh();

        $log = ActivityLog::query()
            ->where(
                'action',
                'UPDATE_SCHOOL_PROFILE'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertNull(
            $log->metadata['old']['logo_path']
        );

        $this->assertNull(
            $log->metadata['old']['favicon_path']
        );

        $this->assertSame(
            $this->school->logo_path,
            $log->metadata['new']['logo_path']
        );

        $this->assertSame(
            $this->school->favicon_path,
            $log->metadata['new']['favicon_path']
        );
    }

    public function test_internal_layout_renders_school_logo(): void
    {
        $logoPath =
            "schools/{$this->school->id}/branding/logo.png";

        $this->school->update([
            'logo_path' => $logoPath,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(
                asset('storage/'.$logoPath),
                false
            )
            ->assertSee(
                'alt="Logo '.$this->school->name.'"',
                false
            );
    }

    public function test_internal_layout_renders_school_favicon(): void
    {
        $faviconPath =
            "schools/{$this->school->id}/branding/favicon.png";

        $this->school->update([
            'favicon_path' => $faviconPath,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(
                asset('storage/'.$faviconPath),
                false
            );
    }

    private function validPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'name' =>
                    'SMK PROFILE TEST',

                'npsn' =>
                    '12345678',

                'address' =>
                    'Alamat Lama',

                'village' =>
                    'Desa Lama',

                'district' =>
                    'Kecamatan Lama',

                'city' =>
                    'Kabupaten Lama',

                'province' =>
                    'Jawa Tengah',

                'postal_code' =>
                    '54311',

                'phone' =>
                    '0287123456',

                'whatsapp' =>
                    '081234567890',

                'email' =>
                    'lama@example.test',

                'website' =>
                    'https://lama.example.test',
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