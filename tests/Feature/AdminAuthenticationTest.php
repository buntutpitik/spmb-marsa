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
        $response = $this->get('/');

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
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
            'password' => 'secret-password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_admin_can_login(): void
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
            'password' => 'secret-password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
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
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/pengaturan')
            ->assertOk();
    }

    public function test_admin_can_access_dashboard_and_settings(): void
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/pengaturan')
            ->assertOk();
    }

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
}