<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Exports\PeriodComparisonExport;
use App\Services\PeriodComparisonService;

class AdminPeriodComparisonExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private PpdbPeriod $periodA;
    private PpdbPeriod $periodB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'name' => 'SMK EXPORT COMPARISON TEST',
        ]);

        $this->periodA = PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'number_year' => 2026,
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $this->periodB = PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'number_year' => 2027,
            'status' => 'OPEN',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_export_period_comparison_excel(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.export', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    }

    public function test_guest_cannot_export_period_comparison_excel(): void
    {
        $this->get(route('admin.comparison.export', [
            'period_a' => $this->periodA->id,
            'period_b' => $this->periodB->id,
        ]))
            ->assertRedirect();
    }

    public function test_same_period_cannot_be_exported_against_itself(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.comparison.export', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodA->id,
            ]))
            ->assertSessionHasErrors();
    }

    public function test_unknown_role_cannot_export_period_comparison_excel(): void
    {
        $user = User::factory()->create([
            'role' => 'UNKNOWN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.comparison.export', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertForbidden();
    }

    public function test_export_filename_contains_both_periods(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.export', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $disposition = $response->headers->get(
            'content-disposition'
        );

        $this->assertNotNull($disposition);

        $this->assertStringContainsString(
            '2026-2027',
            $disposition
        );

        $this->assertStringContainsString(
            '2027-2028',
            $disposition
        );
    }

    public function test_export_contains_all_frozen_comparison_sheets(): void
    {
        $comparison = app(PeriodComparisonService::class)->compare(
            $this->periodA,
            $this->periodB
        );

        $export = new PeriodComparisonExport($comparison);

        $titles = collect($export->sheets())
            ->map(fn ($sheet) => $sheet->title())
            ->all();

        $this->assertSame([
            'Ringkasan',
            'Status',
            'Jurusan',
            'Gender',
            'Jalur Pendaftaran',
            'Asal Data',
            'Sekolah Asal',
            'Referral',
            'Tren Pendaftaran',
            'Tren Hari Pendaftaran',
            'Daftar Ulang & Keuangan',
        ], $titles);
    }

    public function test_registration_day_trend_sheet_contains_expected_columns(): void
    {
        $this->periodA->update([
            'registration_open' => '2026-01-07',
            'registration_close' => '2026-01-20',
        ]);

        $this->periodB->update([
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-01-20',
        ]);

        $comparison = app(PeriodComparisonService::class)->compare(
            $this->periodA->fresh(),
            $this->periodB->fresh()
        );

        $export = new PeriodComparisonExport($comparison);

        $sheet = collect($export->sheets())
            ->first(
                fn ($sheet) =>
                    $sheet->title() === 'Tren Hari Pendaftaran'
            );

        $this->assertNotNull($sheet);

        $rows = $sheet->array();

        $this->assertSame([
            'Hari',
            '2026/2027',
            '2027/2028',
            'Selisih',
            'Kumulatif A',
            'Kumulatif B',
            'Delta Kumulatif',
        ], $rows[0]);
    }

    public function test_registration_day_trend_sheet_contains_correct_numeric_values(): void
    {
        $this->periodA->update([
            'registration_open' => '2026-01-07',
            'registration_close' => '2026-01-20',
        ]);

        $this->periodB->update([
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-01-20',
        ]);

        $comparison = [
            'period_a' => $this->periodA->fresh(),
            'period_b' => $this->periodB->fresh(),

            'registration_day_trend' => [
                [
                    'day' => 1,
                    'count_a' => 2,
                    'count_b' => 1,
                    'delta' => -1,
                    'cumulative_a' => 2,
                    'cumulative_b' => 1,
                    'cumulative_delta' => -1,
                ],
                [
                    'day' => 2,
                    'count_a' => 1,
                    'count_b' => 3,
                    'delta' => 2,
                    'cumulative_a' => 3,
                    'cumulative_b' => 4,
                    'cumulative_delta' => 1,
                ],
            ],
        ];

        $sheet = new \App\Exports\Sheets\PeriodComparisonRegistrationDayTrendSheet(
            $comparison
        );

        $rows = $sheet->array();

        $this->assertSame([
            'Hari ke-1',
            2,
            1,
            -1,
            2,
            1,
            -1,
        ], $rows[1]);

        $this->assertSame([
            'Hari ke-2',
            1,
            3,
            2,
            3,
            4,
            1,
        ], $rows[2]);
    }

    public function test_data_source_sheet_contains_labels_and_self_service_rate(): void
    {
        $comparison = app(PeriodComparisonService::class)->compare(
            $this->periodA,
            $this->periodB
        );

        $export = new PeriodComparisonExport($comparison);

        $sheet = collect($export->sheets())
            ->first(fn ($sheet) => $sheet->title() === 'Asal Data');

        $this->assertNotNull($sheet);

        $rows = $sheet->array();

        $flat = collect($rows)
            ->flatten()
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->all();

        $this->assertContains(
            'Pendaftaran Mandiri',
            $flat
        );

        $this->assertContains(
            'Input Panitia',
            $flat
        );

        $this->assertContains(
            'Self-Service Registration Rate',
            $flat
        );
    }

    public function test_finance_sheet_contains_frozen_finance_metrics(): void
    {
        $comparison = app(PeriodComparisonService::class)->compare(
            $this->periodA,
            $this->periodB
        );

        $export = new PeriodComparisonExport($comparison);

        $sheet = collect($export->sheets())
            ->first(
                fn ($sheet) =>
                    $sheet->title() === 'Daftar Ulang & Keuangan'
            );

        $this->assertNotNull($sheet);

        $rows = $sheet->array();

        $flat = collect($rows)
            ->flatten()
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->all();

        $this->assertContains(
            'Jumlah Daftar Ulang',
            $flat
        );

        $this->assertContains(
            'Jumlah Transaksi',
            $flat
        );

        $this->assertContains(
            'Total Pembayaran',
            $flat
        );
    }

    public function test_data_source_sheet_contains_correct_numeric_values(): void
    {
        $pathA = \App\Models\AdmissionPath::query()->create([
            'period_id' => $this->periodA->id,
            'name' => 'KHUSUS-A',
            'code' => 'KHUSUS-A',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $pathB = \App\Models\AdmissionPath::query()->create([
            'period_id' => $this->periodB->id,
            'name' => 'UMUM-B',
            'code' => 'UMUM-B',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $major = \App\Models\Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'is_active' => true,
        ]);

        $this->makeExportRegistrations(
            $this->periodA,
            $pathA,
            $major,
            [
                'PUBLIC' => 3,
                'ADMIN' => 7,
            ],
            'DSA'
        );

        $this->makeExportRegistrations(
            $this->periodB,
            $pathB,
            $major,
            [
                'PUBLIC' => 8,
                'ADMIN' => 6,
            ],
            'DSB'
        );

        $comparison = app(PeriodComparisonService::class)->compare(
            $this->periodA,
            $this->periodB
        );

        $export = new PeriodComparisonExport($comparison);

        $sheet = collect($export->sheets())
            ->first(fn ($sheet) => $sheet->title() === 'Asal Data');

        $rows = $sheet->array();

        $publicRow = collect($rows)
            ->first(fn ($row) => ($row[0] ?? null) === 'Pendaftaran Mandiri');

        $adminRow = collect($rows)
            ->first(fn ($row) => ($row[0] ?? null) === 'Input Panitia');

        $this->assertSame(3, $publicRow[1]);
        $this->assertSame(8, $publicRow[2]);
        $this->assertSame(5, $publicRow[3]);

        $this->assertSame(7, $adminRow[1]);
        $this->assertSame(6, $adminRow[2]);
        $this->assertSame(-1, $adminRow[3]);

        $this->assertEqualsWithDelta(
            30.0,
            $publicRow[4],
            0.01
        );

        $this->assertEqualsWithDelta(
            57.142857,
            $publicRow[5],
            0.01
        );
    }

    public function test_finance_sheet_contains_correct_numeric_values(): void
    {
        $pathA = \App\Models\AdmissionPath::query()->create([
            'period_id' => $this->periodA->id,
            'name' => 'KHUSUS-A',
            'code' => 'KHUSUS-A',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $pathB = \App\Models\AdmissionPath::query()->create([
            'period_id' => $this->periodB->id,
            'name' => 'UMUM-B',
            'code' => 'UMUM-B',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $major = \App\Models\Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'is_active' => true,
        ]);

        $this->makeExportFinanceRegistration(
            $this->periodA,
            $pathA,
            $major,
            'FA1',
            100000
        );

        $this->makeExportFinanceRegistration(
            $this->periodA,
            $pathA,
            $major,
            'FA2',
            150000
        );

        $this->makeExportFinanceRegistration(
            $this->periodB,
            $pathB,
            $major,
            'FB1',
            100000
        );

        $this->makeExportFinanceRegistration(
            $this->periodB,
            $pathB,
            $major,
            'FB2',
            100000
        );

        $this->makeExportFinanceRegistration(
            $this->periodB,
            $pathB,
            $major,
            'FB3',
            200000
        );

        $comparison = app(PeriodComparisonService::class)->compare(
            $this->periodA,
            $this->periodB
        );

        $export = new PeriodComparisonExport($comparison);

        $sheet = collect($export->sheets())
            ->first(
                fn ($sheet) =>
                    $sheet->title() === 'Daftar Ulang & Keuangan'
            );

        $rows = $sheet->array();

        $reenrolledRow = collect($rows)
            ->first(fn ($row) => ($row[0] ?? null) === 'Jumlah Daftar Ulang');

        $transactionsRow = collect($rows)
            ->first(fn ($row) => ($row[0] ?? null) === 'Jumlah Transaksi');

        $paymentRow = collect($rows)
            ->first(fn ($row) => ($row[0] ?? null) === 'Total Pembayaran');

        $this->assertSame([2, 3, 1], [
            $reenrolledRow[1],
            $reenrolledRow[2],
            $reenrolledRow[3],
        ]);

        $this->assertSame([2, 3, 1], [
            $transactionsRow[1],
            $transactionsRow[2],
            $transactionsRow[3],
        ]);

        $this->assertSame([250000, 400000, 150000], [
            $paymentRow[1],
            $paymentRow[2],
            $paymentRow[3],
        ]);
    }

    private function makeExportRegistrations(
        PpdbPeriod $period,
        \App\Models\AdmissionPath $path,
        \App\Models\Major $major,
        array $sourceCounts,
        string $prefix
    ): void {
        static $sequence = 0;

        foreach ($sourceCounts as $source => $count) {

            for ($i = 0; $i < $count; $i++) {
                $sequence++;

                \App\Models\Registration::query()->create([
                    'period_id' => $period->id,
                    'admission_path_id' => $path->id,
                    'major_id' => $major->id,
                    'registration_number' => $prefix.'-'.$sequence,
                    'nik' => '336600'.str_pad(
                        (string) $sequence,
                        10,
                        '0',
                        STR_PAD_LEFT
                    ),
                    'full_name' => 'EXPORT DATA '.$sequence,
                    'birth_place' => 'KEBUMEN',
                    'birth_date' => '2010-01-01',
                    'gender' => 'L',
                    'religion' => 'ISLAM',
                    'whatsapp' => '0823'.str_pad(
                        (string) $sequence,
                        8,
                        '0',
                        STR_PAD_LEFT
                    ),
                    'data_source' => $source,
                    'status' => 'REGISTERED',
                    'registered_at' => now(),
                ]);
            }
        }
    }

    private function makeExportFinanceRegistration(
        PpdbPeriod $period,
        \App\Models\AdmissionPath $path,
        \App\Models\Major $major,
        string $prefix,
        int $amount
    ): void {
        static $sequence = 50000;

        $sequence++;

        $registration = \App\Models\Registration::query()->create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,
            'registration_number' => $prefix.'-'.$sequence,
            'nik' => '337700'.str_pad(
                (string) $sequence,
                10,
                '0',
                STR_PAD_LEFT
            ),
            'full_name' => 'EXPORT FINANCE '.$sequence,
            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',
            'whatsapp' => '0824'.str_pad(
                (string) $sequence,
                8,
                '0',
                STR_PAD_LEFT
            ),
            'data_source' => 'ADMIN',
            'status' => 'REENROLLED',
            'registered_at' => now(),
            'accepted_at' => now(),
            'reenrolled_at' => now(),
        ]);

        \App\Models\ReenrollmentPayment::query()->create([
            'registration_id' => $registration->id,
            'payment_date' => now(),
            'amount' => $amount,
            'recorded_by' => $this->admin->id,
            'payment_method' => 'CASH',
        ]);
    }
}
