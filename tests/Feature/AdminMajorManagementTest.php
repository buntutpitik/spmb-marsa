<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Major;
use App\Models\PeriodMajor;
use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMajorManagementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PpdbPeriod $period;

    private Major $major;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK MAJOR TEST',
            'npsn' => '11223344',
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

        $this->major = Major::query()->create([
            'school_id' => $this->school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
            'short_name' => 'RPL',
            'description' => null,
            'icon_path' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PeriodMajor::query()->create([
            'period_id' => $this->period->id,
            'major_id' => $this->major->id,
            'quota' => null,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_major_management(): void
    {
        $this->get(
            route('admin.majors.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_non_superadmin_roles_cannot_access_major_management(): void
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
                    route('admin.majors.index')
                )
                ->assertForbidden();
        }
    }

    public function test_superadmin_can_create_major_and_attach_it_to_selected_period(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->post(
                route('admin.majors.store'),
                [
                    'school_id' => $this->school->id,
                    'period_id' => $this->period->id,
                    'code' => ' tkro ',
                    'name' =>
                        ' Teknik Kendaraan Ringan Otomotif ',
                    'short_name' => ' tkro ',
                    'description' =>
                        ' Jurusan kendaraan ringan ',
                    'sort_order' => 2,
                    'quota' => 72,
                ]
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.majors.index',
                    [
                        'period_id' =>
                            $this->period->id,
                    ]
                )
            );

        $major = Major::query()
            ->where('code', 'TKRO')
            ->firstOrFail();

        $this->assertSame(
            $this->school->id,
            $major->school_id
        );

        $this->assertSame(
            'Teknik Kendaraan Ringan Otomotif',
            $major->name
        );

        $this->assertSame(
            'TKRO',
            $major->short_name
        );

        $this->assertDatabaseHas(
            'period_majors',
            [
                'period_id' => $this->period->id,
                'major_id' => $major->id,
                'quota' => 72,
                'is_active' => true,
            ]
        );
    }

    public function test_create_major_allows_nullable_quota(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->post(
                route('admin.majors.store'),
                [
                    'school_id' => $this->school->id,
                    'period_id' => $this->period->id,
                    'code' => 'AKL',
                    'name' =>
                        'Akuntansi dan Keuangan Lembaga',
                    'short_name' => 'AKL',
                    'description' => null,
                    'sort_order' => 3,
                    'quota' => '',
                ]
            )
            ->assertSessionHasNoErrors();

        $major = Major::query()
            ->where('code', 'AKL')
            ->firstOrFail();

        $this->assertDatabaseHas(
            'period_majors',
            [
                'period_id' => $this->period->id,
                'major_id' => $major->id,
                'quota' => null,
                'is_active' => true,
            ]
        );
    }

    public function test_duplicate_major_code_is_rejected_within_same_school(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->from(
                route(
                    'admin.majors.index',
                    [
                        'period_id' =>
                            $this->period->id,
                    ]
                )
            )
            ->post(
                route('admin.majors.store'),
                [
                    'school_id' => $this->school->id,
                    'period_id' => $this->period->id,
                    'code' => 'rpl',
                    'name' => 'Duplikat RPL',
                    'short_name' => 'RPL',
                    'sort_order' => 9,
                    'quota' => null,
                ]
            )
            ->assertSessionHasErrors('code');
    }

    public function test_same_major_code_can_exist_on_different_school(): void
    {
        $otherSchool = School::query()->create([
            'name' => 'SMK LAIN',
            'npsn' => '55667788',
        ]);

        $otherPeriod = PpdbPeriod::query()->create([
            'school_id' => $otherSchool->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => false,
            'number_prefix' => 'LAIN',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 0,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->post(
                route('admin.majors.store'),
                [
                    'school_id' => $otherSchool->id,
                    'period_id' => $otherPeriod->id,
                    'code' => 'RPL',
                    'name' => 'RPL SEKOLAH LAIN',
                    'short_name' => 'RPL',
                    'sort_order' => 1,
                    'quota' => null,
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertSame(
            2,
            Major::query()
                ->where('code', 'RPL')
                ->count()
        );
    }

    public function test_store_rejects_period_from_different_school(): void
    {
        $otherSchool = School::query()->create([
            'name' => 'SMK CROSS TEST',
            'npsn' => '66778899',
        ]);

        $otherPeriod = PpdbPeriod::query()->create([
            'school_id' => $otherSchool->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'registration_open' => '2028-01-01',
            'registration_close' => '2028-06-30',
            'status' => 'DRAFT',
            'is_active' => false,
            'number_prefix' => 'CROSS',
            'number_year' => 2028,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 0,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $response = $this
            ->actingAs($user)
            ->from(
                route('admin.majors.index')
            )
            ->post(
                route('admin.majors.store'),
                [
                    'school_id' => $this->school->id,
                    'period_id' => $otherPeriod->id,
                    'code' => 'TSM',
                    'name' => 'Teknik Sepeda Motor',
                    'short_name' => 'TSM',
                    'sort_order' => 2,
                    'quota' => null,
                ]
            );

        $response
            ->assertRedirect(
                route('admin.majors.index')
            )
            ->assertSessionHasErrors(
                'period_id'
            );

        $this->assertDatabaseMissing(
            'majors',
            [
                'school_id' => $this->school->id,
                'code' => 'TSM',
            ]
        );
    }

    public function test_superadmin_can_update_major_master(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route(
                    'admin.majors.update',
                    $this->major
                ),
                [
                    'period_id' =>
                        $this->period->id,

                    'code' => 'rpl',

                    'name' =>
                        'Rekayasa Perangkat Lunak Baru',

                    'short_name' => 'rpl',

                    'description' =>
                        'Deskripsi baru',

                    'sort_order' => 5,
                ]
            )
            ->assertSessionHasNoErrors();

        $this->major->refresh();

        $this->assertSame(
            'RPL',
            $this->major->code
        );

        $this->assertSame(
            'Rekayasa Perangkat Lunak Baru',
            $this->major->name
        );

        $this->assertSame(
            'RPL',
            $this->major->short_name
        );

        $this->assertSame(
            5,
            $this->major->sort_order
        );
    }

    public function test_superadmin_can_toggle_master_status(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->patch(
                route(
                    'admin.majors.toggle-master',
                    $this->major
                ),
                [
                    'period_id' =>
                        $this->period->id,
                ]
            )
            ->assertSessionHasNoErrors();

        $this->major->refresh();

        $this->assertFalse(
            $this->major->is_active
        );
    }

    public function test_superadmin_can_update_period_major_configuration(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route(
                    'admin.majors.update-period',
                    $this->major
                ),
                [
                    'period_id' =>
                        $this->period->id,

                    'is_active' => '0',
                    'quota' => 108,
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'period_majors',
            [
                'period_id' =>
                    $this->period->id,

                'major_id' =>
                    $this->major->id,

                'quota' => 108,

                'is_active' => false,
            ]
        );
    }

    public function test_update_period_major_can_create_missing_pivot(): void
    {
        PeriodMajor::query()
            ->where(
                'period_id',
                $this->period->id
            )
            ->where(
                'major_id',
                $this->major->id
            )
            ->delete();

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route(
                    'admin.majors.update-period',
                    $this->major
                ),
                [
                    'period_id' =>
                        $this->period->id,

                    'is_active' => '1',

                    'quota' => '',
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'period_majors',
            [
                'period_id' =>
                    $this->period->id,

                'major_id' =>
                    $this->major->id,

                'quota' => null,

                'is_active' => true,
            ]
        );
    }

    public function test_period_major_update_rejects_different_school(): void
    {
        $otherSchool = School::query()->create([
            'name' => 'SMK OTHER PERIOD',
            'npsn' => '99887766',
        ]);

        $otherPeriod = PpdbPeriod::query()->create([
            'school_id' => $otherSchool->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'registration_open' => '2028-01-01',
            'registration_close' => '2028-06-30',
            'status' => 'DRAFT',
            'is_active' => false,
            'number_prefix' => 'OTHER',
            'number_year' => 2028,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 0,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->from(
                route(
                    'admin.majors.index'
                )
            )
            ->put(
                route(
                    'admin.majors.update-period',
                    $this->major
                ),
                [
                    'period_id' =>
                        $otherPeriod->id,

                    'is_active' => '1',
                    'quota' => 10,
                ]
            )
            ->assertSessionHasErrors(
                'period_id'
            );
    }

    public function test_major_mutations_create_activity_logs_with_audit_context(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->withServerVariables([
                'REMOTE_ADDR' =>
                    '203.0.113.60',

                'HTTP_USER_AGENT' =>
                    'SPMB-MARSA-Major-Audit-Test/1.0',
            ])
            ->put(
                route(
                    'admin.majors.update',
                    $this->major
                ),
                [
                    'period_id' =>
                        $this->period->id,

                    'code' => 'RPL',

                    'name' =>
                        'RPL AUDIT',

                    'short_name' => 'RPL',

                    'description' => null,

                    'sort_order' => 1,
                ]
            )
            ->assertSessionHasNoErrors();

        $log = ActivityLog::query()
            ->where(
                'action',
                'UPDATE_MAJOR'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $user->id,
            $log->user_id
        );

        $this->assertSame(
            '203.0.113.60',
            $log->ip_address
        );

        $this->assertSame(
            'SPMB-MARSA-Major-Audit-Test/1.0',
            $log->user_agent
        );

        $this->assertSame(
            $this->major->id,
            $log->metadata['major_id']
        );

        $this->assertSame(
            'Rekayasa Perangkat Lunak',
            $log->metadata['old']['name']
        );

        $this->assertSame(
            'RPL AUDIT',
            $log->metadata['new']['name']
        );
    }

    public function test_closed_period_rejects_major_store(): void
    {
        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->post(
                route('admin.majors.store'),
                [
                    'school_id' => $this->school->id,
                    'period_id' => $this->period->id,
                    'code' => 'TKRO',
                    'name' => 'Teknik Kendaraan Ringan Otomotif',
                    'short_name' => 'TKRO',
                    'description' => null,
                    'sort_order' => 2,
                    'quota' => 72,
                ]
            )
            ->assertStatus(409);

        $this->assertDatabaseMissing('majors', [
            'school_id' => $this->school->id,
            'code' => 'TKRO',
        ]);
    }

    public function test_closed_period_rejects_major_update(): void
    {
        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route(
                    'admin.majors.update',
                    $this->major
                ),
                [
                    'period_id' => $this->period->id,
                    'code' => 'RPL',
                    'name' => 'RPL MUTATED',
                    'short_name' => 'RPL',
                    'description' => null,
                    'sort_order' => 1,
                ]
            )
            ->assertStatus(409);

        $this->assertSame(
            'Rekayasa Perangkat Lunak',
            $this->major->fresh()->name
        );
    }

    public function test_closed_period_rejects_major_master_toggle(): void
    {
        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->patch(
                route(
                    'admin.majors.toggle-master',
                    $this->major
                ),
                [
                    'period_id' => $this->period->id,
                ]
            )
            ->assertStatus(409);

        $this->assertTrue(
            $this->major->fresh()->is_active
        );
    }

    public function test_closed_period_rejects_period_major_update(): void
    {
        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->put(
                route(
                    'admin.majors.update-period',
                    $this->major
                ),
                [
                    'period_id' => $this->period->id,
                    'is_active' => '0',
                    'quota' => 99,
                ]
            )
            ->assertStatus(409);

        $periodMajor = PeriodMajor::query()
            ->where('period_id', $this->period->id)
            ->where('major_id', $this->major->id)
            ->firstOrFail();

        $this->assertNull($periodMajor->quota);
        $this->assertTrue($periodMajor->is_active);
    }

    public function test_closed_period_major_management_is_read_only_in_ui(): void
    {
        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $user = $this->makeUser('SUPERADMIN');

        $response = $this->actingAs($user)
            ->get(
                route(
                    'admin.majors.index',
                    [
                        'period_id' => $this->period->id,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('Periode read-only')
            ->assertSee($this->major->code)
            ->assertSee($this->major->name)
            ->assertSee('Master:')
            ->assertSee('Aktif')
            ->assertSee('Tersedia')
            ->assertSee('Kuota:')
            ->assertDontSee('Tambah Jurusan')
            ->assertDontSee('Simpan Konfigurasi')
            ->assertDontSee('Nonaktifkan Master')
            ->assertDontSee('Aktifkan Master');
    }

    public function test_superadmin_can_access_major_management(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->get(
                route('admin.majors.index')
            )
            ->assertOk()
            ->assertSee('Jurusan')
            ->assertSee($this->major->code)
            ->assertSee($this->major->name)
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