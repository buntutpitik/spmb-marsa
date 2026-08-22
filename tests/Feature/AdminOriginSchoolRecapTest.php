<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminOriginSchoolRecapTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_origin_school_recap(): void
    {
        $this->get(
            route('admin.recaps.origin-schools.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_access_origin_school_recap(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route('admin.recaps.origin-schools.index')
            )
            ->assertOk()
            ->assertSee('Rekap Asal Sekolah')
            ->assertSee('Distribusi Asal Sekolah');
    }

    public function test_origin_school_recap_groups_same_school(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'SMP MAARIF TEST',
            'REGISTERED'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'SMP MAARIF TEST',
            'ACCEPTED'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.recaps.origin-schools.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('SMP MAARIF TEST');

        $this->assertSame(
            1,
            substr_count(
                $response->getContent(),
                'SMP MAARIF TEST'
            )
        );
    }

    public function test_origin_school_recap_uses_selected_period_only(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'SMP PERIODE AKTIF',
            'REGISTERED'
        );

        $otherSchool = School::create([
            'name' => 'SMK OTHER SCHOOL RECAP',
        ]);

        $otherPeriod = PpdbPeriod::create([
            'school_id' => $otherSchool->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'status' => 'OPEN',
            'is_active' => false,
            'number_year' => 2028,
        ]);

        $otherPath = AdmissionPath::create([
            'period_id' => $otherPeriod->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'is_active' => true,
        ]);

        $otherMajor = Major::create([
            'school_id' => $otherSchool->id,
            'code' => 'OTH',
            'name' => 'OTHER',
            'is_active' => true,
        ]);

        $this->makeRegistration(
            $otherPeriod,
            $otherPath,
            $otherMajor,
            'SMP PERIODE LAIN',
            'REGISTERED'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.recaps.origin-schools.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('SMP PERIODE AKTIF')
            ->assertDontSee('SMP PERIODE LAIN');
    }

    public function test_origin_school_recap_can_search_school(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'SMP ALPHA',
            'REGISTERED'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'MTS BETA',
            'REGISTERED'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.recaps.origin-schools.index',
                    [
                        'period_id' => $period->id,
                        'q' => 'ALPHA',
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('SMP ALPHA')
            ->assertDontSee('MTS BETA');
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK ORIGIN RECAP TEST',
        ]);

        $period = PpdbPeriod::create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_year' => 2027,
        ]);

        $path = AdmissionPath::create([
            'period_id' => $period->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'is_active' => true,
        ]);

        $major = Major::create([
            'school_id' => $school->id,
            'code' => 'TST',
            'name' => 'JURUSAN TEST',
            'is_active' => true,
        ]);

        return [
            $user,
            $period,
            $path,
            $major,
        ];
    }

    private function makeRegistration(
        PpdbPeriod $period,
        AdmissionPath $path,
        Major $major,
        string $originSchool,
        string $status
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::create([
            'period_id' => $period->id,
            'wave_id' => null,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,

            'registration_number' =>
                'ORIGIN-RECAP-'.$sequence,

            'nik' =>
                '3355555555'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                'ORIGIN RECAP TEST '.$sequence,

            'origin_school' => $originSchool,

            'whatsapp' =>
                '08125555'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'data_source' => 'ADMIN',
            'status' => $status,
            'registered_at' => now(),

            'accepted_at' =>
                in_array(
                    $status,
                    ['ACCEPTED', 'REENROLLED'],
                    true
                )
                    ? now()
                    : null,

            'rejected_at' =>
                $status === 'REJECTED'
                    ? now()
                    : null,

            'reenrolled_at' =>
                $status === 'REENROLLED'
                    ? now()
                    : null,

            'withdrawn_at' =>
                $status === 'WITHDRAWN'
                    ? now()
                    : null,
        ]);
    }
}