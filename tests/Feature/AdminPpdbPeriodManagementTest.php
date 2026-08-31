<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPpdbPeriodManagementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PpdbPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK PERIOD TEST',
            'npsn' => '12345678',
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
            'principal_name' => 'KEPALA SEKOLAH LAMA',
            'principal_nip' => '123456789',
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
            'notes' => null,
        ]);
    }

    public function test_guest_cannot_access_period_management(): void
    {
        $this->get(
            route('admin.periods.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_superadmin_can_access_period_management(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->get(
                route('admin.periods.index')
            )
            ->assertOk()
            ->assertSee('Periode SPMB');
    }

    public function test_non_superadmin_roles_cannot_access_period_management(): void
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
                    route('admin.periods.index')
                )
                ->assertForbidden();
        }
    }

    public function test_superadmin_can_update_period_settings(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $response = $this->actingAs($user)
            ->put(
                route(
                    'admin.periods.update',
                    $this->period
                ),
                $this->validPayload([
                    'name' => '2027/2028 REVISI',
                    'registration_open' =>
                        '2027-02-01',
                    'registration_close' =>
                        '2027-07-31',
                    'principal_name' =>
                        'KEPALA SEKOLAH BARU',
                    'principal_nip' =>
                        '987654321',
                    'notes' =>
                        'Pengaturan periode diperbarui.',
                ])
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('admin.periods.index')
            );

        $this->assertDatabaseHas(
            'ppdb_periods',
            [
                'id' => $this->period->id,
                'name' => '2027/2028 REVISI',
                'registration_open' =>
                    '2027-02-01',
                'registration_close' =>
                    '2027-07-31',
                'principal_name' =>
                    'KEPALA SEKOLAH BARU',
                'principal_nip' =>
                    '987654321',
                'notes' =>
                    'Pengaturan periode diperbarui.',
            ]
        );
    }

    public function test_superadmin_can_change_reenrollment_fee(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route(
                    'admin.periods.update',
                    $this->period
                ),
                $this->validPayload([
                    /*
                     * Simulasikan input rupiah dari UI.
                     */
                    'default_reenroll_fee' =>
                        '350.000',
                ])
            )
            ->assertSessionHasNoErrors();

        $this->period->refresh();

        $this->assertSame(
            350000,
            (int) $this->period
                ->default_reenroll_fee
        );
    }

    public function test_non_superadmin_cannot_update_period(): void
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
                        'admin.periods.update',
                        $this->period
                    ),
                    $this->validPayload([
                        'default_reenroll_fee' =>
                            999999,
                    ])
                )
                ->assertForbidden();
        }

        $this->period->refresh();

        $this->assertSame(
            250000,
            (int) $this->period
                ->default_reenroll_fee
        );
    }

    public function test_activating_period_deactivates_other_periods(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $otherPeriod = PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'registration_open' => '2028-01-01',
            'registration_close' => '2028-06-30',
            'status' => 'DRAFT',
            'is_active' => false,
            'number_prefix' => 'MARSA',
            'number_year' => 2028,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 400000,
        ]);

        $this->actingAs($user)
            ->put(
                route(
                    'admin.periods.update',
                    $otherPeriod
                ),
                [
                    'name' => '2028/2029',
                    'year_start' => 2028,
                    'year_end' => 2029,
                    'registration_open' =>
                        '2028-01-01',
                    'registration_close' =>
                        '2028-06-30',
                    'status' => 'OPEN',
                    'is_active' => '1',
                    'principal_name' => null,
                    'principal_nip' => null,
                    'number_prefix' => 'MARSA',
                    'number_year' => 2028,
                    'number_digits' => 4,
                    'include_major_code' => '1',
                    'default_reenroll_fee' =>
                        400000,
                    'notes' => null,
                ]
            )
            ->assertSessionHasNoErrors();

        $this->period->refresh();
        $otherPeriod->refresh();

        $this->assertFalse(
            $this->period->is_active
        );

        $this->assertTrue(
            $otherPeriod->is_active
        );

        $this->assertSame(
            1,
            PpdbPeriod::query()
                ->where('is_active', true)
                ->count()
        );
    }

    public function test_invalid_period_dates_are_rejected(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $response = $this->actingAs($user)
            ->from(
                route('admin.periods.index')
            )
            ->put(
                route(
                    'admin.periods.update',
                    $this->period
                ),
                $this->validPayload([
                    'registration_open' =>
                        '2027-06-30',
                    'registration_close' =>
                        '2027-01-01',
                ])
            );

        $response
            ->assertRedirect(
                route('admin.periods.index')
            )
            ->assertSessionHasErrors(
                'registration_close'
            );
    }

    public function test_zero_reenrollment_fee_is_allowed(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route(
                    'admin.periods.update',
                    $this->period
                ),
                $this->validPayload([
                    'default_reenroll_fee' => 0,
                ])
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('admin.periods.index')
            );

        $this->period->refresh();

        $this->assertSame(
            0,
            (int) $this->period
                ->default_reenroll_fee
        );
    }

    public function test_period_update_creates_activity_log_with_audit_context(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->withServerVariables([
                'REMOTE_ADDR' =>
                    '203.0.113.40',

                'HTTP_USER_AGENT' =>
                    'SPMB-MARSA-Period-Audit-Test/1.0',
            ])
            ->put(
                route(
                    'admin.periods.update',
                    $this->period
                ),
                $this->validPayload([
                    'default_reenroll_fee' =>
                        375000,
                ])
            )
            ->assertSessionHasNoErrors();

        $log = ActivityLog::query()
            ->where(
                'action',
                'UPDATE_PPDB_PERIOD'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $user->id,
            $log->user_id
        );

        $this->assertSame(
            '203.0.113.40',
            $log->ip_address
        );

        $this->assertSame(
            'SPMB-MARSA-Period-Audit-Test/1.0',
            $log->user_agent
        );

        $this->assertSame(
            $this->period->id,
            $log->metadata['period_id']
        );

        $this->assertSame(
            250000,
            (int) $log->metadata['old']
                ['default_reenroll_fee']
        );

        $this->assertSame(
            375000,
            (int) $log->metadata['new']
                ['default_reenroll_fee']
        );
    }

    public function test_closing_active_period_automatically_deactivates_it(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->assertSame(
            'OPEN',
            $this->period->status
        );

        $this->assertTrue(
            $this->period->is_active
        );

        $this->actingAs($user)
            ->put(
                route(
                    'admin.periods.update',
                    $this->period
                ),
                $this->validPayload([
                    'status' => 'CLOSED',
                    'is_active' => '1',
                ])
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('admin.periods.index')
            );

        $this->period->refresh();

        $this->assertSame(
            'CLOSED',
            $this->period->status
        );

        $this->assertFalse(
            $this->period->is_active
        );
    }

    public function test_closed_period_is_read_only(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->put(
                route(
                    'admin.periods.update',
                    $this->period
                ),
                $this->validPayload([
                    'name' => 'HISTORI DIUBAH',
                    'status' => 'OPEN',
                    'is_active' => '1',
                    'default_reenroll_fee' => 999999,
                ])
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'error',
                'Periode yang sudah ditutup bersifat read-only dan tidak dapat diubah.'
            )
            ->assertRedirect(
                route('admin.periods.index')
            );

        $this->period->refresh();

        $this->assertSame(
            '2027/2028',
            $this->period->name
        );

        $this->assertSame(
            'CLOSED',
            $this->period->status
        );

        $this->assertFalse(
            $this->period->is_active
        );

        $this->assertSame(
            250000,
            (int) $this->period->default_reenroll_fee
        );
    }

    private function validPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'name' => '2027/2028',
                'year_start' => 2027,
                'year_end' => 2028,
                'registration_open' =>
                    '2027-01-01',
                'registration_close' =>
                    '2027-06-30',
                'status' => 'OPEN',
                'is_active' => '1',
                'principal_name' =>
                    'KEPALA SEKOLAH LAMA',
                'principal_nip' =>
                    '123456789',
                'number_prefix' => 'MARSA',
                'number_year' => 2027,
                'number_digits' => 4,
                'include_major_code' => '1',
                'default_reenroll_fee' =>
                    250000,
                'notes' => null,
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