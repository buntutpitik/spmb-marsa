<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_admin_settings_to_login(): void
    {
        $response = $this->get('/admin/pengaturan');

        $response->assertRedirect(route('login'));
    }

    public function test_public_registration_page_remains_accessible_without_login(): void
    {
        $response = $this->get('/daftar');

        $response->assertOk();
    }

    public function test_active_superadmin_can_login(): void
    {
        $user = $this->makeUser(
            'SUPERADMIN',
            'superadmin@example.test'
        );

        $this->assertUserCanLogin($user);
    }

    public function test_active_admin_can_login(): void
    {
        $user = $this->makeUser(
            'ADMIN',
            'admin@example.test'
        );

        $this->assertUserCanLogin($user);
    }

    public function test_active_panitia_can_login(): void
    {
        $user = $this->makeUser(
            'PANITIA',
            'panitia@example.test'
        );

        $this->assertUserCanLogin($user);
    }

    public function test_active_bendahara_can_login(): void
    {
        $user = $this->makeUser(
            'BENDAHARA',
            'bendahara@example.test'
        );

        $this->assertUserCanLogin($user);
    }

    public function test_unknown_role_cannot_login_to_internal_panel(): void
    {
        $user = User::factory()->create([
            'email' => 'unknown-role@example.test',
            'role' => 'UNKNOWN',
            'is_active' => true,
            'password' => 'secret-password',
        ]);

        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'secret-password',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => false,
            'password' => 'secret-password',
        ]);

        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'secret-password',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_wrong_password_cannot_login(): void
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
            'password' => 'correct-password',
        ]);

        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_superadmin_can_access_dashboard_and_settings(): void
    {
        $user = $this->makeUser(
            'SUPERADMIN',
            'superadmin-access@example.test'
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/pengaturan')
            ->assertOk();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $user = $this->makeUser(
            'ADMIN',
            'admin-access@example.test'
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_panitia_can_access_dashboard(): void
    {
        $user = $this->makeUser(
            'PANITIA',
            'panitia-access@example.test'
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_bendahara_can_access_dashboard(): void
    {
        $user = $this->makeUser(
            'BENDAHARA',
            'bendahara-access@example.test'
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    /*
     * Settings belum diuji sebagai SUPERADMIN-only pada step ini.
     *
     * Route /admin saat ini masih menggunakan middleware existing
     * SUPERADMIN,ADMIN. Pemisahan hak akses per modul dilakukan
     * pada STEP 18A.2.
     */

    public function test_inactive_authenticated_user_is_removed_from_admin_session(): void
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/admin/pengaturan');

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_ends_authenticated_session(): void
    {
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/logout');

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    private function makeUser(
        string $role,
        string $email
    ): User {
        return User::factory()->create([
            'email' => $email,
            'role' => $role,
            'is_active' => true,
            'password' => 'secret-password',
        ]);
    }

    private function assertUserCanLogin(User $user): void
    {
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}
