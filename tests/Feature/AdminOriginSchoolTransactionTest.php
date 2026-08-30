<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\OriginSchool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminOriginSchoolTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_rolls_back_origin_school_when_activity_log_fails(): void
    {
        $user = $this->makeSuperadmin();

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Simulated activity log failure.'
            );
        });

        try {
            $this->actingAs($user)->post(
                route('admin.origin-schools.store'),
                [
                    'name' => 'SMP TRANSACTION TEST',
                    'type' => 'SMP',
                    'sort_order' => 10,
                ]
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated activity log failure.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing('origin_schools', [
            'name' => 'SMP TRANSACTION TEST',
        ]);
    }

    public function test_update_rolls_back_origin_school_when_activity_log_fails(): void
    {
        $user = $this->makeSuperadmin();

        $originSchool = OriginSchool::create([
            'name' => 'SMP NAMA AWAL',
            'type' => 'SMP',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Simulated activity log failure.'
            );
        });

        try {
            $this->actingAs($user)->put(
                route(
                    'admin.origin-schools.update',
                    $originSchool
                ),
                [
                    'name' => 'MTs NAMA BARU',
                    'type' => 'MTs',
                    'sort_order' => 25,
                ]
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated activity log failure.',
                $exception->getMessage()
            );
        }

        $originSchool->refresh();

        $this->assertSame(
            'SMP NAMA AWAL',
            $originSchool->name
        );

        $this->assertSame(
            'SMP',
            $originSchool->type
        );

        $this->assertSame(
            1,
            $originSchool->sort_order
        );
    }

    public function test_toggle_rolls_back_origin_school_when_activity_log_fails(): void
    {
        $user = $this->makeSuperadmin();

        $originSchool = OriginSchool::create([
            'name' => 'SMP TOGGLE TRANSACTION',
            'type' => 'SMP',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Simulated activity log failure.'
            );
        });

        try {
            $this->actingAs($user)->patch(
                route(
                    'admin.origin-schools.toggle',
                    $originSchool
                )
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated activity log failure.',
                $exception->getMessage()
            );
        }

        $originSchool->refresh();

        $this->assertTrue(
            $originSchool->is_active
        );
    }

    private function makeSuperadmin(): User
    {
        return User::factory()->create([
            'name' => 'SUPERADMIN TEST',
            'email' => 'superadmin-origin-transaction@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);
    }
}