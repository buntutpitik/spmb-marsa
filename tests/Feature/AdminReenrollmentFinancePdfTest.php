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

class AdminReenrollmentFinancePdfTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_download_reenrollment_finance_pdf(): void
    {
        [
            ,
            $period,
        ] = $this->makeFixture();

        $this->get(
            route(
                'admin.reports.reenrollment-finance.pdf',
                [
                    'period_id' => $period->id,
                ]
            )
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_download_reenrollment_finance_pdf(): void
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
            'TEST PDF KEUANGAN'
        );

        ReenrollmentPayment::create([
            'registration_id' => $registration->id,
            'amount' => 250000,
            'paid_at' => now(),
            'payment_method' => 'CASH',
            'received_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.reports.reenrollment-finance.pdf',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response->assertOk();

        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get(
                'content-type'
            )
        );

        $this->assertStringContainsString(
            'daftar-ulang-keuangan-2027-2028.pdf',
            (string) $response->headers->get(
                'content-disposition'
            )
        );
    }

    public function test_reenrollment_finance_pdf_only_uses_accepted_and_reenrolled(): void
    {
        [
            ,
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
            'CALON TERDAFTAR'
        );

        $rows = Registration::query()
            ->where(
                'period_id',
                $period->id
            )
            ->whereIn('status', [
                'ACCEPTED',
                'REENROLLED',
            ])
            ->get();

        $this->assertCount(
            2,
            $rows
        );

        $this->assertFalse(
            $rows->contains(
                'full_name',
                'CALON TERDAFTAR'
            )
        );
    }

    public function test_reenrollment_finance_pdf_requires_valid_period(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route(
                    'admin.reports.reenrollment-finance.pdf',
                    [
                        'period_id' => 999999,
                    ]
                )
            )
            ->assertNotFound();
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK FINANCE PDF TEST',
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
                'FIN-PDF-'.$sequence,

            'nik' =>
                '3381818181'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' => $name,

            'origin_school' => 'SMP PDF TEST',

            'whatsapp' =>
                '08128181'
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
                    [
                        'ACCEPTED',
                        'REENROLLED',
                    ],
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