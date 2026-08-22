<?php

namespace Tests\Feature;

use App\Models\OriginSchool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOriginSchoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_origin_school_management(): void
    {
        $this->get(
            route('admin.origin-schools.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_superadmin_can_access_origin_school_management(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->get(route('admin.origin-schools.index'))
            ->assertOk()
            ->assertSee('Asal Sekolah')
            ->assertSee('Tambah Asal Sekolah');
    }

    public function test_admin_can_access_origin_school_management(): void
    {
        $user = $this->makeUser('ADMIN');

        $this->actingAs($user)
            ->get(route('admin.origin-schools.index'))
            ->assertOk()
            ->assertSee('Asal Sekolah');
    }

    public function test_admin_can_create_origin_school(): void
    {
        $user = $this->makeUser('ADMIN');

        $response = $this->actingAs($user)
            ->post(
                route('admin.origin-schools.store'),
                [
                    'name' => 'smp negeri 1 test',
                    'type' => 'SMP',
                    'sort_order' => 10,
                ]
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('admin.origin-schools.index')
            );

        $this->assertDatabaseHas(
            'origin_schools',
            [
                'name' => 'SMP NEGERI 1 TEST',
                'type' => 'SMP',
                'is_active' => true,
                'sort_order' => 10,
            ]
        );
    }

    public function test_new_origin_school_is_active_by_default(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $this->actingAs($user)
            ->post(
                route('admin.origin-schools.store'),
                [
                    'name' => 'MTs TEST BARU',
                    'type' => 'MTs',
                    'sort_order' => 20,
                ]
            )
            ->assertSessionHasNoErrors();

        $school = OriginSchool::query()
            ->where('name', 'MTS TEST BARU')
            ->firstOrFail();

        $this->assertTrue($school->is_active);
    }

    public function test_duplicate_origin_school_name_is_rejected(): void
    {
        $user = $this->makeUser('ADMIN');

        OriginSchool::create([
            'name' => 'SMP DUPLIKAT',
            'type' => 'SMP',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->from(route('admin.origin-schools.index'))
            ->post(
                route('admin.origin-schools.store'),
                [
                    'name' => 'SMP DUPLIKAT',
                    'type' => 'SMP',
                    'sort_order' => 2,
                ]
            );

        $response
            ->assertRedirect(
                route('admin.origin-schools.index')
            )
            ->assertSessionHasErrors('name');

        $this->assertSame(
            1,
            OriginSchool::query()
                ->where('name', 'SMP DUPLIKAT')
                ->count()
        );
    }

    public function test_admin_can_update_origin_school(): void
    {
        $user = $this->makeUser('ADMIN');

        $school = OriginSchool::create([
            'name' => 'SMP NAMA LAMA',
            'type' => 'SMP',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->put(
                route(
                    'admin.origin-schools.update',
                    $school
                ),
                [
                    'name' => 'mts nama baru',
                    'type' => 'MTs',
                    'sort_order' => 25,
                ]
            );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route('admin.origin-schools.index')
            );

        $this->assertDatabaseHas(
            'origin_schools',
            [
                'id' => $school->id,
                'name' => 'MTS NAMA BARU',
                'type' => 'MTs',
                'sort_order' => 25,
                'is_active' => true,
            ]
        );
    }

    public function test_admin_can_deactivate_origin_school(): void
    {
        $user = $this->makeUser('ADMIN');

        $school = OriginSchool::create([
            'name' => 'SMP AKTIF',
            'type' => 'SMP',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->patch(
                route(
                    'admin.origin-schools.toggle',
                    $school
                )
            )
            ->assertRedirect(
                route('admin.origin-schools.index')
            );

        $this->assertDatabaseHas(
            'origin_schools',
            [
                'id' => $school->id,
                'is_active' => false,
            ]
        );
    }

    public function test_admin_can_reactivate_origin_school(): void
    {
        $user = $this->makeUser('SUPERADMIN');

        $school = OriginSchool::create([
            'name' => 'SMP NONAKTIF',
            'type' => 'SMP',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->patch(
                route(
                    'admin.origin-schools.toggle',
                    $school
                )
            )
            ->assertRedirect(
                route('admin.origin-schools.index')
            );

        $this->assertDatabaseHas(
            'origin_schools',
            [
                'id' => $school->id,
                'is_active' => true,
            ]
        );
    }

    public function test_origin_school_management_has_no_delete_route(): void
    {
        $routes = collect(
            app('router')->getRoutes()->getRoutes()
        );

        $deleteRouteExists = $routes->contains(
            function ($route) {
                return in_array(
                    'DELETE',
                    $route->methods(),
                    true
                )
                    && str_contains(
                        $route->uri(),
                        'pengaturan/asal-sekolah'
                    );
            }
        );

        $this->assertFalse($deleteRouteExists);
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create([
            'name' => $role.' TEST',
            'email' => strtolower($role)
                .uniqid()
                .'@example.com',

            'password' => Hash::make(
                'Password123!'
            ),

            'role' => $role,
            'is_active' => true,
        ]);
    }
}