<?php

namespace Tests\Feature;

use App\Exports\ReenrollmentFinanceExport;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\ReenrollmentPayment;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminReenrollmentFinanceExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_export_reenrollment_finance(): void
    {
        [
            ,
            $period,
        ] = $this->makeFixture();

        $this->get(
            route(
                'admin.reports.reenrollment-finance.excel',
                [
                    'period_id' => $period->id,
                ]
            )
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_download_reenrollment_finance_excel(): void
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
            'TEST EXPORT KEUANGAN'
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
                    'admin.reports.reenrollment-finance.excel',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response->assertOk();

        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get(
                'content-type'
            )
        );

        $this->assertStringContainsString(
            'daftar-ulang-keuangan-2027-2028.xlsx',
            (string) $response->headers->get(
                'content-disposition'
            )
        );
    }

    public function test_export_only_contains_accepted_and_reenrolled(): void
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

        $export = new ReenrollmentFinanceExport(
            $period
        );

        $rows = $export->collection();

        $this->assertCount(
            2,
            $rows
        );

        $this->assertTrue(
            $rows->every(
                fn ($registration) =>
                    in_array(
                        $registration->status,
                        [
                            'ACCEPTED',
                            'REENROLLED',
                        ],
                        true
                    )
            )
        );
    }

    public function test_export_calculates_partial_payment_correctly(): void
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
            'CALON CICILAN'
        );

        ReenrollmentPayment::create([
            'registration_id' => $registration->id,
            'amount' => 100000,
            'paid_at' => now(),
            'payment_method' => 'CASH',
            'received_by' => $user->id,
        ]);

        $export = new ReenrollmentFinanceExport(
            $period
        );

        $row = $export->map(
            $export->collection()->first()
        );

        $this->assertSame(
            250000,
            $row[4]
        );

        $this->assertSame(
            100000,
            $row[5]
        );

        $this->assertSame(
            150000,
            $row[6]
        );

        $this->assertSame(
            'Belum Lunas',
            $row[7]
        );
    }

    public function test_export_requires_valid_period(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route(
                    'admin.reports.reenrollment-finance.excel',
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
            'name' => 'SMK FINANCE EXPORT TEST',
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
                'FIN-EXPORT-'.$sequence,

            'nik' =>
                '3373737373'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' => $name,

            'origin_school' => 'SMP TEST',

            'whatsapp' =>
                '08127373'
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