<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class AdminUserTransactionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_rolls_back_user_when_activity_log_fails(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin);

        Event::listen(
            'eloquent.creating: '.ActivityLog::class,
            function (): void {
                throw new RuntimeException(
                    'Forced activity log failure.'
                );
            }
        );

        try {
            $this->withoutExceptionHandling()
                ->post(
                    route('admin.users.store'),
                    [
                        'name' => 'Atomic User Test',
                        'email' => 'atomic-user@example.test',
                        'role' => 'ADMIN',
                        'password' => 'Password123!',
                        'password_confirmation' => 'Password123!',
                    ]
                );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced activity log failure.',
                $exception->getMessage()
            );
        } finally {
            Event::forget(
                'eloquent.creating: '.ActivityLog::class
            );
        }

        $this->assertDatabaseMissing('users', [
            'email' => 'atomic-user@example.test',
        ]);

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'CREATE_USER',
        ]);
    }

    public function test_update_rolls_back_user_when_activity_log_fails(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'name' => 'Original User',
            'email' => 'original-user@example.test',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin);

        $this->withoutExceptionHandling();

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Forced activity log failure.'
            );
        });

        try {
            $this->put(
                route('admin.users.update', $target),
                [
                    'name' => 'Updated User',
                    'email' => 'updated-user@example.test',
                    'role' => 'PANITIA',
                ]
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced activity log failure.',
                $exception->getMessage()
            );
        } finally {
            ActivityLog::flushEventListeners();
        }

        $target->refresh();

        $this->assertSame(
            'Original User',
            $target->name
        );

        $this->assertSame(
            'original-user@example.test',
            $target->email
        );

        $this->assertSame(
            'ADMIN',
            $target->role
        );

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'UPDATE_USER',
        ]);
    }

    public function test_toggle_active_rolls_back_user_when_activity_log_fails(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin);

        $this->withoutExceptionHandling();

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Forced activity log failure.'
            );
        });

        try {
            $this->patch(
                route(
                    'admin.users.toggle-active',
                    $target
                )
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced activity log failure.',
                $exception->getMessage()
            );
        } finally {
            ActivityLog::flushEventListeners();
        }

        $target->refresh();

        $this->assertTrue(
            (bool) $target->is_active
        );

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'TOGGLE_USER_ACTIVE',
        ]);
    }

    public function test_reset_password_rolls_back_user_when_activity_log_fails(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'password' => 'OriginalPassword123!',
            'is_active' => true,
        ]);

        $oldPasswordHash = $target->password;

        $this->actingAs($superadmin);

        $this->withoutExceptionHandling();

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Forced activity log failure.'
            );
        });

        try {
            $this->patch(
                route(
                    'admin.users.reset-password',
                    $target
                ),
                [
                    'password' => 'NewPassword123!',
                    'password_confirmation' =>
                        'NewPassword123!',
                ]
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced activity log failure.',
                $exception->getMessage()
            );
        } finally {
            ActivityLog::flushEventListeners();
        }

        $target->refresh();

        $this->assertSame(
            $oldPasswordHash,
            $target->password
        );

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'RESET_USER_PASSWORD',
        ]);
    }
}