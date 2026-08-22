<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\ReenrollmentPayment;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminReenrollmentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_reenrollment_page(): void
    {
        $this->get(
            route('admin.reenrollments.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_access_reenrollment_page(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(route('admin.reenrollments.index'))
            ->assertOk()
            ->assertSee('Daftar Ulang')
            ->assertSee('Menunggu Lunas')
            ->assertSee('Sudah Lunas')
            ->assertSee('Total Pembayaran Masuk');
    }

    public function test_reenrollment_page_only_lists_accepted_and_reenrolled(): void
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
            'CALON DITERIMA'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'REENROLLED',
            'CALON DAFTAR ULANG'
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
                route('admin.reenrollments.index', [
                    'period_id' => $period->id,
                ])
            );

        $response
            ->assertOk()
            ->assertSee('CALON DITERIMA')
            ->assertSee('CALON DAFTAR ULANG')
            ->assertDontSee('CALON BARU');
    }

    public function test_reenrollment_page_can_filter_waiting_and_paid_off(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $accepted = $this->makeRegistration(
            $period,
            $path,
            $major,
            'ACCEPTED',
            'CALON BELUM LUNAS'
        );

        $reenrolled = $this->makeRegistration(
            $period,
            $path,
            $major,
            'REENROLLED',
            'CALON SUDAH LUNAS'
        );

        ReenrollmentPayment::create([
            'registration_id' => $accepted->id,
            'amount' => 100000,
            'paid_at' => now(),
        ]);

        ReenrollmentPayment::create([
            'registration_id' => $reenrolled->id,
            'amount' => 250000,
            'paid_at' => now(),
        ]);

        $waitingResponse = $this
            ->actingAs($user)
            ->get(
                route('admin.reenrollments.index', [
                    'period_id' => $period->id,
                    'payment_status' => 'WAITING',
                ])
            );

        $waitingResponse
            ->assertOk()
            ->assertSee('CALON BELUM LUNAS')
            ->assertDontSee('CALON SUDAH LUNAS');

        $paidOffResponse = $this
            ->actingAs($user)
            ->get(
                route('admin.reenrollments.index', [
                    'period_id' => $period->id,
                    'payment_status' => 'PAID_OFF',
                ])
            );

        $paidOffResponse
            ->assertOk()
            ->assertSee('CALON SUDAH LUNAS')
            ->assertDontSee('CALON BELUM LUNAS');
    }

    public function test_reenrollment_summary_uses_selected_period_only(): void
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
            'ACCEPTED',
            'CALON AKTIF'
        );

        ReenrollmentPayment::create([
            'registration_id' => $registration->id,
            'amount' => 100000,
            'paid_at' => now(),
        ]);

        $otherSchool = School::create([
            'name' => 'SMK OTHER REENROLLMENT',
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
            'registration_id' => $otherRegistration->id,
            'amount' => 250000,
            'paid_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.reenrollments.index', [
                    'period_id' => $period->id,
                ])
            );

        $response
            ->assertOk()
            ->assertSee('Rp 100.000')
            ->assertDontSee('CALON PERIODE LAIN');
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK REENROLLMENT TEST',
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
            'registration_number' => 'REEN-TEST-'.$sequence,
            'nik' => '3322222222'.str_pad(
                (string) $sequence,
                6,
                '0',
                STR_PAD_LEFT
            ),
            'full_name' => $name,
            'origin_school' => 'SMP TEST',
            'whatsapp' => '08122222'.str_pad(
                (string) $sequence,
                4,
                '0',
                STR_PAD_LEFT
            ),
            'data_source' => 'ADMIN',
            'status' => $status,
            'registered_at' => now(),
            'accepted_at' => in_array(
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