<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PeriodMajor;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminRecapTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_recap_page(): void
    {
        $this->get(
            route('admin.recaps.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_access_recap_page(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(route('admin.recaps.index'))
            ->assertOk()
            ->assertSee('Rekap SPMB')
            ->assertSee('Rekap per Jurusan');
    }

    public function test_recap_only_uses_selected_period(): void
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
            'REGISTERED',
            'L'
        );

        $otherSchool = School::create([
            'name' => 'SMK REKAP LAIN',
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
        ]);

        $otherMajor = Major::create([
            'school_id' => $otherSchool->id,
            'code' => 'OTH',
            'name' => 'OTHER',
            'is_active' => true,
        ]);

        PeriodMajor::create([
            'period_id' => $otherPeriod->id,
            'major_id' => $otherMajor->id,
            'is_active' => true,
        ]);

        $this->makeRegistration(
            $otherPeriod,
            $otherPath,
            $otherMajor,
            'REGISTERED',
            'L'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.recaps.index', [
                    'period_id' => $period->id,
                ])
            );

        $response
            ->assertOk()
            ->assertSee('Total Pendaftar')
            ->assertSee('1');
    }

    public function test_recap_counts_status_and_gender_per_major(): void
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
            'REGISTERED',
            'L'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'ACCEPTED',
            'P'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'REENROLLED',
            'L'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.recaps.index', [
                    'period_id' => $period->id,
                ])
            );

        $response
            ->assertOk()
            ->assertSee('TST')
            ->assertSee('JURUSAN TEST');
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK REKAP TEST',
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
            'sort_order' => 1,
        ]);

        PeriodMajor::create([
            'period_id' => $period->id,
            'major_id' => $major->id,
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
        string $status,
        string $gender
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,

            'registration_number' =>
                'RECAP-TEST-'.$sequence,

            'nik' =>
                '3344444444'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                'REKAP TEST '.$sequence,

            'gender' => $gender,
            'origin_school' => 'SMP TEST',

            'whatsapp' =>
                '08124444'
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