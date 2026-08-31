<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_change_password_page(): void
    {
        $this->get('/akun/password')
            ->assertRedirect(route('login'));
    }

    public function test_all_internal_roles_can_access_change_password_page(): void
    {
        foreach (
            ['SUPERADMIN', 'ADMIN', 'PANITIA', 'BENDAHARA']
            as $role
        ) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($user)
                ->get('/akun/password')
                ->assertOk()
                ->assertSee('Ubah Password');
        }
    }

    public function test_user_can_change_own_password(): void
    {
        $user = User::factory()->create([
            'role' => 'PANITIA',
            'is_active' => true,
            'password' => 'PasswordLama123!',
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/akun/password')
            ->put('/akun/password', [
                'current_password' => 'PasswordLama123!',
                'password' => 'PasswordBaru456!',
                'password_confirmation' => 'PasswordBaru456!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/akun/password');

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                'PasswordBaru456!',
                $user->password
            )
        );

        $log = ActivityLog::query()
            ->where('action', 'CHANGE_OWN_PASSWORD')
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($log);

        $this->assertSame(
            $user->id,
            (int) ($log->metadata['target_user_id'] ?? 0)
        );

        $metadata = json_encode(
            $log->metadata,
            JSON_THROW_ON_ERROR
        );

        $this->assertStringNotContainsString(
            'PasswordLama123!',
            $metadata
        );

        $this->assertStringNotContainsString(
            'PasswordBaru456!',
            $metadata
        );
    }

    public function test_current_password_must_be_correct(): void
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
            'password' => 'PasswordLama123!',
        ]);

        $oldHash = $user->password;

        $this->actingAs($user)
            ->from('/akun/password')
            ->put('/akun/password', [
                'current_password' => 'PasswordSalah123!',
                'password' => 'PasswordBaru456!',
                'password_confirmation' => 'PasswordBaru456!',
            ])
            ->assertRedirect('/akun/password')
            ->assertSessionHasErrors('current_password');

        $user->refresh();

        $this->assertSame(
            $oldHash,
            $user->password
        );
    }

    public function test_new_password_requires_confirmation(): void
    {
        $user = User::factory()->create([
            'role' => 'BENDAHARA',
            'is_active' => true,
            'password' => 'PasswordLama123!',
        ]);

        $this->actingAs($user)
            ->from('/akun/password')
            ->put('/akun/password', [
                'current_password' => 'PasswordLama123!',
                'password' => 'PasswordBaru456!',
                'password_confirmation' => 'TidakSama456!',
            ])
            ->assertRedirect('/akun/password')
            ->assertSessionHasErrors('password');
    }

    public function test_new_password_must_have_minimum_length(): void
    {
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
            'password' => 'PasswordLama123!',
        ]);

        $this->actingAs($user)
            ->from('/akun/password')
            ->put('/akun/password', [
                'current_password' => 'PasswordLama123!',
                'password' => 'Baru123',
                'password_confirmation' => 'Baru123',
            ])
            ->assertRedirect('/akun/password')
            ->assertSessionHasErrors('password');
    }

    public function test_new_password_must_be_different_from_current_password(): void
    {
        $user = User::factory()->create([
            'role' => 'PANITIA',
            'is_active' => true,
            'password' => 'PasswordSama123!',
        ]);

        $this->actingAs($user)
            ->from('/akun/password')
            ->put('/akun/password', [
                'current_password' => 'PasswordSama123!',
                'password' => 'PasswordSama123!',
                'password_confirmation' => 'PasswordSama123!',
            ])
            ->assertRedirect('/akun/password')
            ->assertSessionHasErrors('password');
    }

    public function test_internal_topbar_contains_change_password_link(): void
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ubah Password')
            ->assertSee(
                route('account.password.edit'),
                false
            );
    }
}