<?php

namespace Tests\Feature;

use App\Exports\OriginSchoolRecapExport;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminOriginSchoolRecapExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_export_origin_school_recap(): void
    {
        [
            ,
            $period,
        ] = $this->makeFixture();

        $this->get(
            route(
                'admin.reports.origin-school-recap.excel',
                [
                    'period_id' => $period->id,
                ]
            )
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_download_origin_school_recap_excel(): void
    {
        [
            $user,
            $period,
        ] = $this->makeFixture();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.reports.origin-school-recap.excel',
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
            'rekap-asal-sekolah-2027-2028.xlsx',
            (string) $response->headers->get(
                'content-disposition'
            )
        );
    }

    public function test_origin_school_recap_groups_same_school_and_counts_status(): void
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

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'MTS LAIN',
            'REENROLLED'
        );

        $export = new OriginSchoolRecapExport(
            $period
        );

        $rows = $export->collection();

        /*
         * SMP MAARIF TEST
         * MTS LAIN
         * TOTAL
         */
        $this->assertCount(
            3,
            $rows
        );

        $schoolRow = $rows
            ->firstWhere(
                'origin_school',
                'SMP MAARIF TEST'
            );

        $this->assertNotNull(
            $schoolRow
        );

        $this->assertSame(
            2,
            (int) $schoolRow->total
        );

        $this->assertSame(
            1,
            (int) $schoolRow->registered
        );

        $this->assertSame(
            1,
            (int) $schoolRow->accepted
        );

        $this->assertSame(
            0,
            (int) $schoolRow->reenrolled
        );

        $totalRow = $rows->last();

        $this->assertSame(
            'TOTAL',
            $totalRow->origin_school
        );

        $this->assertSame(
            3,
            (int) $totalRow->total
        );
    }

    public function test_origin_school_recap_excludes_empty_origin_school(): void
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
            null,
            'REGISTERED'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'SMP VALID',
            'REGISTERED'
        );

        $export = new OriginSchoolRecapExport(
            $period
        );

        $rows = $export->collection();

        /*
         * SMP VALID + TOTAL
         */
        $this->assertCount(
            2,
            $rows
        );

        $this->assertSame(
            'SMP VALID',
            $rows->first()->origin_school
        );
    }

    public function test_origin_school_recap_only_uses_selected_period(): void
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
            'SMP PERIODE AKTIF',
            'REGISTERED'
        );

        $otherSchool = School::create([
            'name' => 'SMK OTHER ORIGIN EXPORT',
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
            'SMP PERIODE LAIN',
            'REGISTERED'
        );

        $export = new OriginSchoolRecapExport(
            $period
        );

        $rows = $export->collection();

        $this->assertCount(
            2,
            $rows
        );

        $this->assertSame(
            'SMP PERIODE AKTIF',
            $rows->first()->origin_school
        );
    }

    public function test_origin_school_recap_requires_valid_period(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route(
                    'admin.reports.origin-school-recap.excel',
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
            'name' => 'SMK ORIGIN EXPORT TEST',
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
        ?string $originSchool,
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
                'ORIGIN-EXPORT-'.$sequence,

            'nik' =>
                '3375757575'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                'ORIGIN EXPORT '.$sequence,

            'origin_school' => $originSchool,

            'whatsapp' =>
                '08127575'
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