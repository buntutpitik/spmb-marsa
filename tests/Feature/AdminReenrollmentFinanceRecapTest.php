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

class AdminReenrollmentFinanceRecapTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_finance_recap(): void
    {
        $this->get(
            route(
                'admin.recaps.reenrollment-finance.index'
            )
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_access_finance_recap(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route(
                    'admin.recaps.reenrollment-finance.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'Rekap Keuangan Daftar Ulang'
            )
            ->assertSee(
                'Total Pembayaran Masuk'
            );
    }

    public function test_finance_recap_only_lists_accepted_and_reenrolled(): void
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
            'ACCEPTED',
            'CALON BELUM LUNAS'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'REENROLLED',
            'CALON LUNAS'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'REGISTERED',
            'CALON BARU'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.recaps.reenrollment-finance.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('CALON BELUM LUNAS')
            ->assertSee('CALON LUNAS')
            ->assertDontSee('CALON BARU');
    }

    public function test_finance_recap_calculates_billed_paid_and_remaining(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $first = $this->makeRegistration(
            $period,
            $path,
            $major,
            'ACCEPTED',
            'CALON CICILAN'
        );

        $second = $this->makeRegistration(
            $period,
            $path,
            $major,
            'REENROLLED',
            'CALON LUNAS'
        );

        ReenrollmentPayment::create([
            'registration_id' => $first->id,
            'amount' => 100000,
            'paid_at' => now(),
        ]);

        ReenrollmentPayment::create([
            'registration_id' => $second->id,
            'amount' => 250000,
            'paid_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.recaps.reenrollment-finance.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('Rp 500.000')
            ->assertSee('Rp 350.000')
            ->assertSee('Rp 150.000');
    }

    public function test_finance_recap_uses_selected_period_only(): void
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
            'CALON PERIODE AKTIF'
        );

        ReenrollmentPayment::create([
            'registration_id' => $registration->id,
            'amount' => 250000,
            'paid_at' => now(),
        ]);

        $otherSchool = School::create([
            'name' => 'SMK FINANCE OTHER',
        ]);

        $otherPeriod = PpdbPeriod::create([
            'school_id' => $otherSchool->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'status' => 'OPEN',
            'is_active' => false,
            'number_year' => 2028,
            'default_reenroll_fee' => 250000,
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

        $otherRegistration = $this->makeRegistration(
            $otherPeriod,
            $otherPath,
            $otherMajor,
            'REENROLLED',
            'CALON PERIODE LAIN'
        );

        ReenrollmentPayment::create([
            'registration_id' =>
                $otherRegistration->id,
            'amount' => 250000,
            'paid_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.recaps.reenrollment-finance.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('CALON PERIODE AKTIF')
            ->assertDontSee('CALON PERIODE LAIN');
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK FINANCE RECAP TEST',
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
            'admission_path_id' => $path->id,
            'major_id' => $major->id,

            'registration_number' =>
                'FINANCE-RECAP-'.$sequence,

            'nik' =>
                '3388888888'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' => $name,

            'origin_school' => 'SMP TEST',

            'whatsapp' =>
                '08128888'
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