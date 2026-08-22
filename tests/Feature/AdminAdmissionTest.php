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

class AdminAdmissionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_admission_page(): void
    {
        $this->get(
            route('admin.admissions.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_access_admission_page(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(route('admin.admissions.index'))
            ->assertOk()
            ->assertSee('Penerimaan')
            ->assertSee('Menunggu Seleksi')
            ->assertSee('Diterima')
            ->assertSee('Ditolak');
    }

    public function test_admission_page_only_uses_selected_period(): void
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
            'PENDAFTAR PERIODE AKTIF'
        );

        $otherSchool = School::create([
            'name' => 'SMK OTHER TEST',
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
        ]);

        $this->makeRegistration(
            $otherPeriod,
            $otherPath,
            $otherMajor,
            'REGISTERED',
            'PENDAFTAR PERIODE LAIN'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.admissions.index', [
                    'period_id' => $period->id,
                ])
            );

        $response
            ->assertOk()
            ->assertSee('PENDAFTAR PERIODE AKTIF')
            ->assertDontSee('PENDAFTAR PERIODE LAIN');
    }

    public function test_admission_page_can_filter_status(): void
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
            'CALON MENUNGGU'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'ACCEPTED',
            'CALON DITERIMA'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.admissions.index', [
                    'period_id' => $period->id,
                    'status' => 'ACCEPTED',
                ])
            );

        $response
            ->assertOk()
            ->assertSee('CALON DITERIMA')
            ->assertDontSee('CALON MENUNGGU');
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK ADMISSION TEST',
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
        string $status,
        string $name
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::create([
            'period_id' => $period->id,
            'wave_id' => null,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,
            'registration_number' => 'ADM-TEST-'.$sequence,
            'nik' => '3311111111'.str_pad(
                (string) $sequence,
                6,
                '0',
                STR_PAD_LEFT
            ),
            'full_name' => $name,
            'origin_school' => 'SMP TEST',
            'whatsapp' => '08121111'.str_pad(
                (string) $sequence,
                4,
                '0',
                STR_PAD_LEFT
            ),
            'data_source' => 'ADMIN',
            'status' => $status,
            'registered_at' => now(),
            'accepted_at' =>
                $status === 'ACCEPTED'
                    ? now()
                    : null,
        ]);
    }
}