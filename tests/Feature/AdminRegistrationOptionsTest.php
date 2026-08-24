<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\User;

class AdminRegistrationOptionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_superadmin_can_manage_relief_options_and_special_programs(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2027-02-15 10:00:00',
                config('app.timezone')
            )
        );

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin);

        $now = now();

        /*
         * ---------------------------------------------------------
         * Foundation data.
         * ---------------------------------------------------------
         */
        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'SMK TEST ADMIN OPTIONS',
            'npsn' => '77777777',
            'address' => null,
            'village' => null,
            'district' => null,
            'city' => 'Kebumen',
            'province' => 'Jawa Tengah',
            'postal_code' => '54311',
            'phone' => null,
            'whatsapp' => null,
            'email' => null,
            'website' => null,
            'logo_path' => null,
            'favicon_path' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $periodId = DB::table('ppdb_periods')->insertGetId([
            'school_id' => $schoolId,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => true,
            'principal_name' => null,
            'principal_nip' => null,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
            'notes' => null,
            'archived_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('admission_paths')->insert([
            'period_id' => $periodId,
            'name' => 'Khusus',
            'code' => 'KHUSUS',
            'start_date' => '2027-01-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'sort_order' => 1,
            'description' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $majorId = DB::table('majors')->insertGetId([
            'school_id' => $schoolId,
            'code' => 'TKRO',
            'name' => 'Teknik Kendaraan Ringan Otomotif',
            'short_name' => 'TKRO',
            'description' => null,
            'icon_path' => null,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('period_majors')->insert([
            'period_id' => $periodId,
            'major_id' => $majorId,
            'quota' => null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /*
         * ---------------------------------------------------------
         * 1. CREATE Keringanan.
         * ---------------------------------------------------------
         */
        $response = $this->post(
            route('admin.relief-options.store'),
            [
                'period_id' => $periodId,
                'name' => 'TEST ADMIN KERINGANAN',
                'description' => 'Deskripsi awal',
                'sort_order' => 10,
            ]
        );

        $response->assertSessionHasNoErrors();

        $reliefId = DB::table('relief_options')
            ->where('name', 'TEST ADMIN KERINGANAN')
            ->value('id');

        $this->assertNotNull($reliefId);

        $this->assertDatabaseHas('relief_options', [
            'id' => $reliefId,
            'name' => 'TEST ADMIN KERINGANAN',
            'is_active' => 1,
            'sort_order' => 10,
        ]);

        $this->assertDatabaseHas('period_relief_options', [
            'ppdb_period_id' => $periodId,
            'relief_option_id' => $reliefId,
            'is_active' => 1,
            'sort_order' => 10,
        ]);

        /*
         * ---------------------------------------------------------
         * 2. UPDATE Keringanan.
         * ---------------------------------------------------------
         */
        $response = $this->put(
            route('admin.relief-options.update', [
                'reliefOption' => $reliefId,
            ]),
            [
                'period_id' => $periodId,
                'name' => 'TEST ADMIN KERINGANAN EDIT',
                'description' => 'Deskripsi berubah',
                'sort_order' => 11,
            ]
        );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('relief_options', [
            'id' => $reliefId,
            'name' => 'TEST ADMIN KERINGANAN EDIT',
            'sort_order' => 11,
        ]);

        $this->assertDatabaseHas('period_relief_options', [
            'ppdb_period_id' => $periodId,
            'relief_option_id' => $reliefId,
            'sort_order' => 11,
        ]);

        /*
         * ---------------------------------------------------------
         * 3. TOGGLE Keringanan pada periode.
         * ---------------------------------------------------------
         */
        $this->patch(
            route('admin.relief-options.toggle-period', [
                'reliefOption' => $reliefId,
            ]),
            [
                'period_id' => $periodId,
            ]
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('period_relief_options', [
            'ppdb_period_id' => $periodId,
            'relief_option_id' => $reliefId,
            'is_active' => 0,
        ]);

        /*
         * Tidak boleh tampil pada form publik.
         */
        $this->get(route('registration.create'))
            ->assertOk()
            ->assertDontSee('TEST ADMIN KERINGANAN EDIT');

        /*
         * Aktifkan kembali pada periode.
         */
        $this->patch(
            route('admin.relief-options.toggle-period', [
                'reliefOption' => $reliefId,
            ]),
            [
                'period_id' => $periodId,
            ]
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('period_relief_options', [
            'ppdb_period_id' => $periodId,
            'relief_option_id' => $reliefId,
            'is_active' => 1,
        ]);

        /*
         * ---------------------------------------------------------
         * 4. TOGGLE master Keringanan.
         * ---------------------------------------------------------
         */
        $this->patch(
            route('admin.relief-options.toggle-master', [
                'reliefOption' => $reliefId,
            ]),
            [
                'period_id' => $periodId,
            ]
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('relief_options', [
            'id' => $reliefId,
            'is_active' => 0,
        ]);

        /*
         * Master nonaktif => tidak boleh tampil walaupun periode aktif.
         */
        $this->get(route('registration.create'))
            ->assertOk()
            ->assertDontSee('TEST ADMIN KERINGANAN EDIT');

        /*
         * Aktifkan master kembali.
         */
        $this->patch(
            route('admin.relief-options.toggle-master', [
                'reliefOption' => $reliefId,
            ]),
            [
                'period_id' => $periodId,
            ]
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('relief_options', [
            'id' => $reliefId,
            'is_active' => 1,
        ]);

        $this->get(route('registration.create'))
            ->assertOk()
            ->assertSee('TEST ADMIN KERINGANAN EDIT');

        /*
         * ---------------------------------------------------------
         * 5. CREATE Program Khusus.
         * ---------------------------------------------------------
         */
        $response = $this->post(
            route('admin.special-programs.store'),
            [
                'period_id' => $periodId,
                'name' => 'TEST ADMIN PROGRAM',
                'description' => 'Program test',
                'sort_order' => 20,
            ]
        );

        $response->assertSessionHasNoErrors();

        $programId = DB::table('special_programs')
            ->where('name', 'TEST ADMIN PROGRAM')
            ->value('id');

        $this->assertNotNull($programId);

        $this->assertDatabaseHas('special_programs', [
            'id' => $programId,
            'name' => 'TEST ADMIN PROGRAM',
            'is_active' => 1,
            'sort_order' => 20,
        ]);

        $this->assertDatabaseHas('period_special_programs', [
            'ppdb_period_id' => $periodId,
            'special_program_id' => $programId,
            'is_active' => 1,
            'sort_order' => 20,
        ]);

        /*
         * ---------------------------------------------------------
         * 6. UPDATE Program Khusus.
         * ---------------------------------------------------------
         */
        $response = $this->put(
            route('admin.special-programs.update', [
                'specialProgram' => $programId,
            ]),
            [
                'period_id' => $periodId,
                'name' => 'TEST ADMIN PROGRAM EDIT',
                'description' => 'Program berubah',
                'sort_order' => 21,
            ]
        );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('special_programs', [
            'id' => $programId,
            'name' => 'TEST ADMIN PROGRAM EDIT',
            'sort_order' => 21,
        ]);

        $this->assertDatabaseHas('period_special_programs', [
            'ppdb_period_id' => $periodId,
            'special_program_id' => $programId,
            'sort_order' => 21,
        ]);

        /*
         * ---------------------------------------------------------
         * 7. TOGGLE Program Khusus pada periode.
         * ---------------------------------------------------------
         */
        $this->patch(
            route('admin.special-programs.toggle-period', [
                'specialProgram' => $programId,
            ]),
            [
                'period_id' => $periodId,
            ]
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('period_special_programs', [
            'ppdb_period_id' => $periodId,
            'special_program_id' => $programId,
            'is_active' => 0,
        ]);

        $this->get(route('registration.create'))
            ->assertOk()
            ->assertDontSee('TEST ADMIN PROGRAM EDIT');

        /*
         * Aktifkan kembali.
         */
        $this->patch(
            route('admin.special-programs.toggle-period', [
                'specialProgram' => $programId,
            ]),
            [
                'period_id' => $periodId,
            ]
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('period_special_programs', [
            'ppdb_period_id' => $periodId,
            'special_program_id' => $programId,
            'is_active' => 1,
        ]);

        /*
         * ---------------------------------------------------------
         * 8. TOGGLE master Program Khusus.
         * ---------------------------------------------------------
         */
        $this->patch(
            route('admin.special-programs.toggle-master', [
                'specialProgram' => $programId,
            ]),
            [
                'period_id' => $periodId,
            ]
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('special_programs', [
            'id' => $programId,
            'is_active' => 0,
        ]);

        $this->get(route('registration.create'))
            ->assertOk()
            ->assertDontSee('TEST ADMIN PROGRAM EDIT');

        /*
         * Aktifkan master kembali.
         */
        $this->patch(
            route('admin.special-programs.toggle-master', [
                'specialProgram' => $programId,
            ]),
            [
                'period_id' => $periodId,
            ]
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('special_programs', [
            'id' => $programId,
            'is_active' => 1,
        ]);

        $this->get(route('registration.create'))
            ->assertOk()
            ->assertSee('TEST ADMIN PROGRAM EDIT');

        /*
         * ---------------------------------------------------------
         * 9. Pastikan tidak ada hard-delete route.
         * ---------------------------------------------------------
         */
        $routeNames = collect(
            app('router')->getRoutes()->getRoutes()
        )
            ->map(fn ($route) => $route->getName())
            ->filter();

        $this->assertFalse(
            $routeNames->contains('admin.relief-options.destroy')
        );

        $this->assertFalse(
            $routeNames->contains('admin.special-programs.destroy')
        );
    }
}