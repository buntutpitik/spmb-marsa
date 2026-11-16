<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ReferralSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AdminReferralSourceTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_rolls_back_when_activity_log_fails(): void
    {
        $user = $this->makeSuperadmin();

        $this->simulateActivityLogFailure();

        $this->expectActivityLogFailure(function () use ($user): void {
            $this->actingAs($user)->post(
                route('admin.referral-sources.store'),
                ['name' => 'ALUMNI TRANSACTION']
            );
        });

        $this->assertDatabaseMissing('referral_sources', [
            'name' => 'ALUMNI TRANSACTION',
        ]);
    }

    public function test_update_rolls_back_when_activity_log_fails(): void
    {
        $user = $this->makeSuperadmin();

        $source = ReferralSource::create([
            'name' => 'NAMA AWAL',
            'is_active' => true,
        ]);

        $this->simulateActivityLogFailure();

        $this->expectActivityLogFailure(function () use ($user, $source): void {
            $this->actingAs($user)->put(
                route('admin.referral-sources.update', $source),
                ['name' => 'NAMA BARU']
            );
        });

        $source->refresh();

        $this->assertSame('NAMA AWAL', $source->name);
        $this->assertTrue($source->is_active);
    }

    public function test_toggle_rolls_back_when_activity_log_fails(): void
    {
        $user = $this->makeSuperadmin();

        $source = ReferralSource::create([
            'name' => 'SUMBER AKTIF',
            'is_active' => true,
        ]);

        $this->simulateActivityLogFailure();

        $this->expectActivityLogFailure(function () use ($user, $source): void {
            $this->actingAs($user)->patch(
                route('admin.referral-sources.toggle', $source)
            );
        });

        $this->assertTrue($source->fresh()->is_active);
    }

    private function simulateActivityLogFailure(): void
    {
        $this->withoutExceptionHandling();

        ActivityLog::creating(function (): void {
            throw new RuntimeException(
                'Simulated activity log failure.'
            );
        });
    }

    private function expectActivityLogFailure(callable $callback): void
    {
        $caught = false;

        try {
            $callback();
        } catch (RuntimeException $exception) {
            $caught = true;

            $this->assertSame(
                'Simulated activity log failure.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            $caught,
            'Expected activity log failure was not thrown.'
        );
    }

    private function makeSuperadmin(): User
    {
        return User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);
    }
}
