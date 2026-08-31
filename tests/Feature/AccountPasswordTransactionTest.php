<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class AccountPasswordTransactionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_change_own_password_rolls_back_when_activity_log_fails(): void
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
            'password' => 'OriginalPassword123!',
        ]);

        $oldPasswordHash = $user->password;

        $this->actingAs($user);

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
                ->put(
                    route('account.password.update'),
                    [
                        'current_password' =>
                            'OriginalPassword123!',
                        'password' =>
                            'NewPassword456!',
                        'password_confirmation' =>
                            'NewPassword456!',
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

        $user->refresh();

        $this->assertSame(
            $oldPasswordHash,
            $user->password
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' => 'CHANGE_OWN_PASSWORD',
                'user_id' => $user->id,
            ]
        );
    }
}