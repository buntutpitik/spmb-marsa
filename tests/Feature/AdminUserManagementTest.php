<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_user_management(): void
    {
        $this->get('/admin/users')
            ->assertRedirect(route('login'));
    }

    public function test_superadmin_can_access_user_management(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        $this->actingAs($superadmin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Tambah User')
            ->assertSee($superadmin->email);
    }

    public function test_admin_cannot_access_user_management(): void
    {
        $this->actingAs($this->makeUser('ADMIN'))
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_panitia_cannot_access_user_management(): void
    {
        $this->actingAs($this->makeUser('PANITIA'))
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_bendahara_cannot_access_user_management(): void
    {
        $this->actingAs($this->makeUser('BENDAHARA'))
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_superadmin_can_create_user(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        $response = $this
            ->actingAs($superadmin)
            ->post('/admin/users', [
                'name' => 'Panitia Baru',
                'email' => 'panitia.baru@example.test',
                'role' => 'PANITIA',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/users');

        $user = User::query()
            ->where('email', 'panitia.baru@example.test')
            ->firstOrFail();

        $this->assertSame('Panitia Baru', $user->name);
        $this->assertSame('PANITIA', $user->role);
        $this->assertTrue($user->is_active);

        $this->assertTrue(
            Hash::check('Password123!', $user->password)
        );

        $this->assertActivity(
            'CREATE_USER',
            $superadmin,
            $user
        );
    }

    public function test_create_user_rejects_invalid_role(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        $this
            ->actingAs($superadmin)
            ->from('/admin/users')
            ->post('/admin/users', [
                'name' => 'Role Salah',
                'email' => 'role.salah@example.test',
                'role' => 'OWNER',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect('/admin/users')
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'role.salah@example.test',
        ]);
    }

    public function test_create_user_requires_unique_email(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');
        $existing = $this->makeUser('ADMIN');

        $this
            ->actingAs($superadmin)
            ->from('/admin/users')
            ->post('/admin/users', [
                'name' => 'Email Duplikat',
                'email' => $existing->email,
                'role' => 'PANITIA',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect('/admin/users')
            ->assertSessionHasErrors('email');
    }

    public function test_superadmin_can_update_another_user(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');
        $target = $this->makeUser('PANITIA');

        $response = $this
            ->actingAs($superadmin)
            ->put('/admin/users/'.$target->id, [
                'name' => 'Panitia Diperbarui',
                'email' => 'panitia.updated@example.test',
                'role' => 'BENDAHARA',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/users');

        $target->refresh();

        $this->assertSame('Panitia Diperbarui', $target->name);
        $this->assertSame('panitia.updated@example.test', $target->email);
        $this->assertSame('BENDAHARA', $target->role);

        $this->assertActivity(
            'UPDATE_USER',
            $superadmin,
            $target
        );
    }

    public function test_superadmin_cannot_downgrade_own_role(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        $response = $this
            ->actingAs($superadmin)
            ->from('/admin/users')
            ->put('/admin/users/'.$superadmin->id, [
                'name' => $superadmin->name,
                'email' => $superadmin->email,
                'role' => 'ADMIN',
            ]);

        $response
            ->assertRedirect('/admin/users')
            ->assertSessionHasErrors('role');

        $superadmin->refresh();

        $this->assertSame('SUPERADMIN', $superadmin->role);
    }

    public function test_superadmin_can_deactivate_another_user(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');
        $target = $this->makeUser('PANITIA');

        $this
            ->actingAs($superadmin)
            ->patch('/admin/users/'.$target->id.'/toggle-active')
            ->assertRedirect('/admin/users');

        $target->refresh();

        $this->assertFalse($target->is_active);

        $this->assertActivity(
            'TOGGLE_USER_ACTIVE',
            $superadmin,
            $target
        );
    }

    public function test_superadmin_can_reactivate_another_user(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        $target = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => false,
        ]);

        $this
            ->actingAs($superadmin)
            ->patch('/admin/users/'.$target->id.'/toggle-active')
            ->assertRedirect('/admin/users');

        $target->refresh();

        $this->assertTrue($target->is_active);
    }

    public function test_superadmin_cannot_deactivate_own_account(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        $response = $this
            ->actingAs($superadmin)
            ->from('/admin/users')
            ->patch('/admin/users/'.$superadmin->id.'/toggle-active');

        $response
            ->assertRedirect('/admin/users')
            ->assertSessionHasErrors('user');

        $superadmin->refresh();

        $this->assertTrue($superadmin->is_active);
    }

    public function test_superadmin_can_reset_another_users_password(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        $target = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
            'password' => 'OldPassword123!',
        ]);

        $oldPasswordHash = $target->password;

        $response = $this
            ->actingAs($superadmin)
            ->patch('/admin/users/'.$target->id.'/reset-password', [
                'password' => 'NewPassword456!',
                'password_confirmation' => 'NewPassword456!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/users');

        $target->refresh();

        $this->assertNotSame(
            $oldPasswordHash,
            $target->password
        );

        $this->assertTrue(
            Hash::check(
                'NewPassword456!',
                $target->password
            )
        );

        $this->assertActivity(
            'RESET_USER_PASSWORD',
            $superadmin,
            $target
        );
    }

    public function test_reset_password_requires_confirmation(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');
        $target = $this->makeUser('ADMIN');

        $this
            ->actingAs($superadmin)
            ->from('/admin/users')
            ->patch('/admin/users/'.$target->id.'/reset-password', [
                'password' => 'NewPassword456!',
                'password_confirmation' => 'PasswordTidakSama!',
            ])
            ->assertRedirect('/admin/users')
            ->assertSessionHasErrors('password');
    }

    public function test_user_management_can_search_and_filter(): void
    {
        $superadmin = $this->makeUser('SUPERADMIN');

        User::factory()->create([
            'name' => 'Panitia Khusus',
            'email' => 'panitia.khusus@example.test',
            'role' => 'PANITIA',
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Bendahara Lain',
            'email' => 'bendahara.lain@example.test',
            'role' => 'BENDAHARA',
            'is_active' => false,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/users?q=khusus&role=PANITIA&status=ACTIVE')
            ->assertOk()
            ->assertSee('Panitia Khusus')
            ->assertDontSee('Bendahara Lain');
    }

    private function makeUser(string $role): User
    {
        static $sequence = 0;

        $sequence++;

        return User::factory()->create([
            'name' => $role.' USER TEST',
            'email' => strtolower($role)
                .'.users.'
                .$sequence
                .'@example.test',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function assertActivity(
        string $action,
        User $actor,
        User $target
    ): void {
        $log = ActivityLog::query()
            ->where('action', $action)
            ->where('user_id', $actor->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull(
            $log,
            "Activity log {$action} tidak ditemukan."
        );

        $this->assertSame(
            $target->id,
            (int) (
                $log->metadata['target_user_id']
                ?? 0
            )
        );
    }
}
