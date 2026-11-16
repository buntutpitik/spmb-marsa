<?php

namespace Tests\Feature;

use App\Models\ReferralSource;
use App\Models\User;
use App\Models\School;
use App\Models\PpdbPeriod;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReferralSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalized_duplicate_referral_source_name_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        ReferralSource::create([
            'name' => 'ALUMNI',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->from(route('admin.referral-sources.index'))
            ->post(route('admin.referral-sources.store'), [
                'name' => ' alumni ',
            ])
            ->assertRedirect(route('admin.referral-sources.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame(
            1,
            ReferralSource::query()->count()
        );
    }

    public function test_referral_source_name_is_normalized_when_created(): void
    {
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('admin.referral-sources.store'), [
                'name' => '  wali murid  ',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('referral_sources', [
            'name' => 'WALI MURID',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_referral_source_management(): void
    {
        $this->get(route('admin.referral-sources.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_manage_referral_sources(): void
    {
        foreach (['ADMIN', 'PANITIA', 'BENDAHARA'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $source = ReferralSource::create([
                'name' => 'SUMBER '.$role,
                'is_active' => true,
            ]);

            $this->actingAs($user)
                ->get(route('admin.referral-sources.index'))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('admin.referral-sources.store'), [
                    'name' => 'DILARANG '.$role,
                ])
                ->assertForbidden();

            $this->actingAs($user)
                ->put(route('admin.referral-sources.update', $source), [
                    'name' => 'DIUBAH '.$role,
                ])
                ->assertForbidden();

            $this->actingAs($user)
                ->patch(route('admin.referral-sources.toggle', $source))
                ->assertForbidden();

            $this->assertDatabaseHas('referral_sources', [
                'id' => $source->id,
                'name' => 'SUMBER '.$role,
                'is_active' => true,
            ]);
        }
    }

    public function test_superadmin_can_update_referral_source(): void
    {
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $source = ReferralSource::create([
            'name' => 'NAMA LAMA',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('admin.referral-sources.update', $source), [
                'name' => '  nama baru  ',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.referral-sources.index'));

        $this->assertDatabaseHas('referral_sources', [
            'id' => $source->id,
            'name' => 'NAMA BARU',
            'is_active' => true,
        ]);
    }

    public function test_superadmin_can_toggle_referral_source(): void
    {
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $source = ReferralSource::create([
            'name' => 'SUMBER AKTIF',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.referral-sources.toggle', $source))
            ->assertRedirect(route('admin.referral-sources.index'));

        $this->assertFalse($source->fresh()->is_active);

        $this->actingAs($user)
            ->patch(route('admin.referral-sources.toggle', $source))
            ->assertRedirect(route('admin.referral-sources.index'));

        $this->assertTrue($source->fresh()->is_active);
    }

    public function test_referral_source_changes_write_activity_logs(): void
    {
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('admin.referral-sources.store'), [
                'name' => 'ALUMNI',
            ])
            ->assertSessionHasNoErrors();

        $source = ReferralSource::query()
            ->where('name', 'ALUMNI')
            ->firstOrFail();

        $this->actingAs($user)
            ->put(route('admin.referral-sources.update', $source), [
                'name' => 'WALI MURID',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->patch(route('admin.referral-sources.toggle', $source))
            ->assertRedirect(route('admin.referral-sources.index'));

        foreach ([
            'CREATE_REFERRAL_SOURCE',
            'UPDATE_REFERRAL_SOURCE',
            'TOGGLE_REFERRAL_SOURCE',
        ] as $action) {
            $this->assertDatabaseHas('activity_logs', [
                'user_id' => $user->id,
                'registration_id' => null,
                'action' => $action,
            ]);
        }
    }

    public function test_referral_source_management_has_no_delete_route(): void
    {
        $routes = collect(
            app('router')->getRoutes()->getRoutes()
        );

        $deleteRouteExists = $routes->contains(
            fn ($route) =>
                in_array('DELETE', $route->methods(), true)
                && str_contains(
                    $route->uri(),
                    'pengaturan/asal-pembawa'
                )
        );

        $this->assertFalse($deleteRouteExists);
    }

    public function test_updating_or_deactivating_referral_source_preserves_historical_registration(): void
    {
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK HISTORIS TEST',
        ]);

        $period = PpdbPeriod::create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_year' => 2027,
        ]);

        $path = AdmissionPath::create([
            'period_id' => $period->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'is_active' => true,
        ]);

        $major = Major::create([
            'school_id' => $school->id,
            'code' => 'TST',
            'name' => 'JURUSAN TEST',
            'is_active' => true,
        ]);

        $source = ReferralSource::create([
            'name' => 'ALUMNI',
            'is_active' => true,
        ]);

        $registration = Registration::create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,
            'registration_number' => 'HIST-REF-0001',
            'nik' => '3366666666000001',
            'full_name' => 'SISWA HISTORIS',
            'origin_school' => 'SMP TEST',
            'referrer_name' => 'PAK BUDI',
            'referrer_source' => 'ALUMNI',
            'whatsapp' => '081266660001',
            'data_source' => 'ADMIN',
            'status' => 'REGISTERED',
            'registered_at' => now(),
        ]);

        $this->actingAs($user)
            ->put(route('admin.referral-sources.update', $source), [
                'name' => 'KELUARGA ALUMNI',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->patch(route('admin.referral-sources.toggle', $source))
            ->assertRedirect(route('admin.referral-sources.index'));

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'referrer_name' => 'PAK BUDI',
            'referrer_source' => 'ALUMNI',
        ]);

        $this->assertDatabaseHas('referral_sources', [
            'id' => $source->id,
            'name' => 'KELUARGA ALUMNI',
            'is_active' => false,
        ]);
    }

    public function test_superadmin_can_view_referral_source_management_page(): void
    {
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        ReferralSource::create([
            'name' => 'ALUMNI',
            'is_active' => true,
        ]);

        ReferralSource::create([
            'name' => 'WALI MURID',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin.referral-sources.index'))
            ->assertOk()
            ->assertViewIs('admin.settings.referral-sources.index')
            ->assertSee('Master Asal Pembawa')
            ->assertSee('ALUMNI')
            ->assertSee('WALI MURID')
            ->assertSee('Nonaktif')
            ->assertSee('Kelola');
    }

    public function test_superadmin_can_see_referral_source_link_in_settings(): void
    {
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Asal Pembawa')
            ->assertSee('Kelola Asal Pembawa')
            ->assertSee(
                route('admin.referral-sources.index')
            );
    }
}
