<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdmissionPath;
use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdmissionPathManagementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PpdbPeriod $period;

    private AdmissionPath $khusus;

    private AdmissionPath $umum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK ADMISSION PATH TEST',
            'npsn' => '12121212',
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
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 350000,
        ]);

        $this->khusus = AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'Khusus',
            'code' => 'KHUSUS',
            'start_date' => '2027-01-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'sort_order' => 1,
            'description' => null,
        ]);

        $this->umum = AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'Umum',
            'code' => 'UMUM',
            'start_date' => '2027-04-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 2,
            'description' => null,
        ]);
    }

    public function test_guest_cannot_access_admission_path_management(): void
    {
        $this->get(
            route('admin.admission-paths.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_non_superadmin_roles_cannot_access_admission_path_management(): void
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
                    route('admin.admission-paths.index')
                )
                ->assertForbidden();
        }
    }

    public function test_superadmin_can_create_non_overlapping_admission_path(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->post(
                route('admin.admission-paths.store'),
                [
                    'period_id' => $this->period->id,
                    'name' => 'Gelombang Tambahan',
                    'code' => ' tambahan ',
                    'start_date' => '2027-07-01',
                    'end_date' => '2027-07-31',
                    'sort_order' => 3,
                    'description' => ' Jalur tambahan ',
                ]
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.admission-paths.index',
                    [
                        'period_id' => $this->period->id,
                    ]
                )
            );

        $this->assertDatabaseHas(
            'admission_paths',
            [
                'period_id' => $this->period->id,
                'name' => 'Gelombang Tambahan',
                'code' => 'TAMBAHAN',
                'start_date' => '2027-07-01',
                'end_date' => '2027-07-31',
                'is_active' => true,
                'sort_order' => 3,
                'description' => 'Jalur tambahan',
            ]
        );
    }

    public function test_duplicate_code_is_rejected_within_same_period(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->from(
                route(
                    'admin.admission-paths.index',
                    [
                        'period_id' => $this->period->id,
                    ]
                )
            )
            ->post(
                route('admin.admission-paths.store'),
                [
                    'period_id' => $this->period->id,
                    'name' => 'Duplikat',
                    'code' => ' khusus ',
                    'start_date' => '2027-07-01',
                    'end_date' => '2027-07-31',
                    'sort_order' => 9,
                ]
            )
            ->assertSessionHasErrors('code');
    }

    public function test_same_code_can_exist_on_different_period(): void
    {
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
            'default_reenroll_fee' => 350000,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->post(
                route('admin.admission-paths.store'),
                [
                    'period_id' => $otherPeriod->id,
                    'name' => 'Khusus',
                    'code' => 'KHUSUS',
                    'start_date' => '2028-01-01',
                    'end_date' => '2028-03-31',
                    'sort_order' => 1,
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertSame(
            2,
            AdmissionPath::query()
                ->where('code', 'KHUSUS')
                ->count()
        );
    }

    public function test_overlapping_active_path_is_rejected_on_create(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->from(
                route(
                    'admin.admission-paths.index',
                    [
                        'period_id' => $this->period->id,
                    ]
                )
            )
            ->post(
                route('admin.admission-paths.store'),
                [
                    'period_id' => $this->period->id,
                    'name' => 'Overlap',
                    'code' => 'OVERLAP',
                    'start_date' => '2027-03-15',
                    'end_date' => '2027-04-15',
                    'sort_order' => 3,
                ]
            )
            ->assertSessionHasErrors('start_date');

        $this->assertDatabaseMissing(
            'admission_paths',
            [
                'period_id' => $this->period->id,
                'code' => 'OVERLAP',
            ]
        );
    }

    public function test_adjacent_date_ranges_are_allowed(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->post(
                route('admin.admission-paths.store'),
                [
                    'period_id' => $this->period->id,
                    'name' => 'Tahap Akhir',
                    'code' => 'AKHIR',
                    'start_date' => '2027-07-01',
                    'end_date' => '2027-07-31',
                    'sort_order' => 3,
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'admission_paths',
            [
                'period_id' => $this->period->id,
                'code' => 'AKHIR',
            ]
        );
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->from(
                route('admin.admission-paths.index')
            )
            ->post(
                route('admin.admission-paths.store'),
                [
                    'period_id' => $this->period->id,
                    'name' => 'Tanggal Salah',
                    'code' => 'SALAH',
                    'start_date' => '2027-08-10',
                    'end_date' => '2027-08-01',
                    'sort_order' => 3,
                ]
            )
            ->assertSessionHasErrors('end_date');
    }

    public function test_superadmin_can_update_admission_path(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route(
                    'admin.admission-paths.update',
                    $this->khusus
                ),
                [
                    'period_id' => $this->period->id,
                    'name' => ' Khusus Baru ',
                    'code' => ' khusus ',
                    'start_date' => '2027-01-01',
                    'end_date' => '2027-03-31',
                    'sort_order' => 5,
                    'description' => ' Deskripsi baru ',
                ]
            )
            ->assertSessionHasNoErrors();

        $this->khusus->refresh();

        $this->assertSame(
            'Khusus Baru',
            $this->khusus->name
        );

        $this->assertSame(
            'KHUSUS',
            $this->khusus->code
        );

        $this->assertSame(
            5,
            $this->khusus->sort_order
        );

        $this->assertSame(
            'Deskripsi baru',
            $this->khusus->description
        );
    }

    public function test_update_rejects_moving_path_to_another_period(): void
    {
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
            'default_reenroll_fee' => 0,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->from(
                route('admin.admission-paths.index')
            )
            ->put(
                route(
                    'admin.admission-paths.update',
                    $this->khusus
                ),
                [
                    'period_id' => $otherPeriod->id,
                    'name' => 'Khusus',
                    'code' => 'KHUSUS',
                    'start_date' => '2027-01-01',
                    'end_date' => '2027-03-31',
                    'sort_order' => 1,
                ]
            )
            ->assertSessionHasErrors('period_id');

        $this->khusus->refresh();

        $this->assertSame(
            $this->period->id,
            $this->khusus->period_id
        );
    }

    public function test_update_rejects_overlap_with_another_active_path(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->from(
                route(
                    'admin.admission-paths.index',
                    [
                        'period_id' => $this->period->id,
                    ]
                )
            )
            ->put(
                route(
                    'admin.admission-paths.update',
                    $this->umum
                ),
                [
                    'period_id' => $this->period->id,
                    'name' => 'Umum',
                    'code' => 'UMUM',
                    'start_date' => '2027-03-20',
                    'end_date' => '2027-06-30',
                    'sort_order' => 2,
                ]
            )
            ->assertSessionHasErrors('start_date');
    }

    public function test_superadmin_can_deactivate_admission_path(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->patch(
                route(
                    'admin.admission-paths.toggle',
                    $this->khusus
                ),
                [
                    'period_id' => $this->period->id,
                ]
            )
            ->assertSessionHasNoErrors();

        $this->khusus->refresh();

        $this->assertFalse(
            $this->khusus->is_active
        );
    }

    public function test_non_overlapping_inactive_path_can_be_reactivated(): void
    {
        $path = AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'Tahap Juli',
            'code' => 'JULI',
            'start_date' => '2027-07-01',
            'end_date' => '2027-07-31',
            'is_active' => false,
            'sort_order' => 3,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->patch(
                route(
                    'admin.admission-paths.toggle',
                    $path
                ),
                [
                    'period_id' => $this->period->id,
                ]
            )
            ->assertSessionHasNoErrors();

        $path->refresh();

        $this->assertTrue(
            $path->is_active
        );
    }

    public function test_overlapping_inactive_path_cannot_be_reactivated(): void
    {
        $path = AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'Overlap Nonaktif',
            'code' => 'OVERLAP',
            'start_date' => '2027-03-15',
            'end_date' => '2027-04-15',
            'is_active' => false,
            'sort_order' => 3,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->from(
                route(
                    'admin.admission-paths.index',
                    [
                        'period_id' => $this->period->id,
                    ]
                )
            )
            ->patch(
                route(
                    'admin.admission-paths.toggle',
                    $path
                ),
                [
                    'period_id' => $this->period->id,
                ]
            )
            ->assertSessionHasErrors(
                'start_date'
            );

        $path->refresh();

        $this->assertFalse(
            $path->is_active
        );
    }

    public function test_toggle_rejects_path_from_another_period(): void
    {
        $otherPeriod = PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'status' => 'DRAFT',
            'is_active' => false,
            'number_prefix' => 'MARSA',
            'number_year' => 2028,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 0,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->from(
                route('admin.admission-paths.index')
            )
            ->patch(
                route(
                    'admin.admission-paths.toggle',
                    $this->khusus
                ),
                [
                    'period_id' => $otherPeriod->id,
                ]
            )
            ->assertSessionHasErrors('period_id');
    }

    public function test_admission_path_mutation_creates_activity_log_with_audit_context(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->withServerVariables([
                'REMOTE_ADDR' =>
                    '203.0.113.70',

                'HTTP_USER_AGENT' =>
                    'SPMB-MARSA-Admission-Path-Audit-Test/1.0',
            ])
            ->put(
                route(
                    'admin.admission-paths.update',
                    $this->khusus
                ),
                [
                    'period_id' => $this->period->id,
                    'name' => 'Khusus Audit',
                    'code' => 'KHUSUS',
                    'start_date' => '2027-01-01',
                    'end_date' => '2027-03-31',
                    'sort_order' => 1,
                    'description' => null,
                ]
            )
            ->assertSessionHasNoErrors();

        $log = ActivityLog::query()
            ->where(
                'action',
                'UPDATE_ADMISSION_PATH'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $user->id,
            $log->user_id
        );

        $this->assertSame(
            '203.0.113.70',
            $log->ip_address
        );

        $this->assertSame(
            'SPMB-MARSA-Admission-Path-Audit-Test/1.0',
            $log->user_agent
        );

        $this->assertSame(
            $this->khusus->id,
            $log->metadata['admission_path_id']
        );

        $this->assertSame(
            'Khusus',
            $log->metadata['old']['name']
        );

        $this->assertSame(
            'Khusus Audit',
            $log->metadata['new']['name']
        );
    }

    public function test_superadmin_can_access_admission_path_management(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->get(
                route('admin.admission-paths.index')
            )
            ->assertOk()
            ->assertSee('Jalur Pendaftaran')
            ->assertSee($this->khusus->code)
            ->assertSee($this->khusus->name)
            ->assertSee($this->umum->code)
            ->assertSee($this->period->name);
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