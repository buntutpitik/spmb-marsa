<?php

namespace Tests\Feature;

use App\Exports\MajorRecapExport;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PeriodMajor;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminMajorRecapExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_export_major_recap(): void
    {
        [
            ,
            $period,
        ] = $this->makeFixture();

        $this->get(
            route(
                'admin.reports.major-recap.excel',
                [
                    'period_id' => $period->id,
                ]
            )
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_download_major_recap_excel(): void
    {
        [
            $user,
            $period,
        ] = $this->makeFixture();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.reports.major-recap.excel',
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
            'rekap-jurusan-2027-2028.xlsx',
            (string) $response->headers->get(
                'content-disposition'
            )
        );
    }

    public function test_major_recap_counts_gender_and_status(): void
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

        $export = new MajorRecapExport(
            $period
        );

        $rows = $export->collection();

        $this->assertCount(
            2,
            $rows
        );

        $majorRow = $rows->first();

        $this->assertSame(
            2,
            (int) $majorRow->male
        );

        $this->assertSame(
            1,
            (int) $majorRow->female
        );

        $this->assertSame(
            3,
            (int) $majorRow->total
        );

        $this->assertSame(
            1,
            (int) $majorRow->registered
        );

        $this->assertSame(
            1,
            (int) $majorRow->accepted
        );

        $this->assertSame(
            1,
            (int) $majorRow->reenrolled
        );

        $totalRow = $rows->last();

        $this->assertSame(
            'TOTAL',
            $totalRow->code
        );

        $this->assertSame(
            3,
            (int) $totalRow->total
        );
    }

    public function test_major_recap_only_uses_selected_period(): void
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
            'REGISTERED',
            'L'
        );

        $otherSchool = School::create([
            'name' => 'SMK OTHER MAJOR EXPORT',
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

        $export = new MajorRecapExport(
            $period
        );

        $rows = $export->collection();

        $this->assertCount(
            2,
            $rows
        );

        $this->assertSame(
            'TST',
            $rows->first()->code
        );
    }

    public function test_major_recap_requires_valid_period(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route(
                    'admin.reports.major-recap.excel',
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
            'name' => 'SMK MAJOR EXPORT TEST',
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
                'MAJOR-EXPORT-'.$sequence,

            'nik' =>
                '3374747474'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                'MAJOR EXPORT '.$sequence,

            'gender' => $gender,

            'origin_school' => 'SMP TEST',

            'whatsapp' =>
                '08127474'
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