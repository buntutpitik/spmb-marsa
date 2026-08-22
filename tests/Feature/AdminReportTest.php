<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\ReenrollmentPayment;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_report_center(): void
    {
        $this->get(
            route('admin.reports.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_access_report_center(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route('admin.reports.index')
            )
            ->assertOk()
            ->assertSee('Pusat Laporan')
            ->assertSee('Data Pendaftar')
            ->assertSee('Laporan Penerimaan')
            ->assertSeeText('Daftar Ulang & Keuangan')
            ->assertSee('Rekap Jurusan')
            ->assertSee('Rekap Asal Sekolah')
            ->assertSee('Referral / Pembawa');
    }

    public function test_report_summary_uses_selected_period_only(): void
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
            'PENDAFTAR AKTIF'
        );

        $otherSchool = School::create([
            'name' => 'SMK REPORT OTHER',
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
                route(
                    'admin.reports.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response->assertOk();

        $summary = $response->viewData(
            'summary'
        );

        $this->assertSame(
            1,
            $summary['TOTAL']
        );

        $this->assertSame(
            1,
            $summary['REGISTERED']
        );
    }

    public function test_report_summary_calculates_payment_for_selected_period(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $registration = $this->makeRegistration(
            $period,
            $path,
            $major,
            'REENROLLED',
            'PENDAFTAR LUNAS'
        );

        ReenrollmentPayment::create([
            'registration_id' =>
                $registration->id,
            'amount' => 250000,
            'paid_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.reports.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response->assertOk();

        $summary = $response->viewData(
            'summary'
        );

        $this->assertSame(
            250000,
            $summary['TOTAL_PAYMENT']
        );
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK REPORT TEST',
        ]);

        $period = PpdbPeriod::create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_year' => 2027,
            'default_reenroll_fee' => 250000,
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

            'registration_number' =>
                'REPORT-'.$sequence,

            'nik' =>
                '3370707070'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' => $name,

            'origin_school' => 'SMP TEST',

            'whatsapp' =>
                '08127070'
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

            'reenrolled_at' =>
                $status === 'REENROLLED'
                    ? now()
                    : null,
        ]);
    }
}