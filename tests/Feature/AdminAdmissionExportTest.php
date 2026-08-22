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

class AdminAdmissionExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_export_admissions(): void
    {
        [
            ,
            $period,
        ] = $this->makeFixture();

        $this->get(
            route(
                'admin.reports.admissions.excel',
                [
                    'period_id' => $period->id,
                ]
            )
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_download_admission_excel(): void
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
            'TEST EXPORT PENERIMAAN'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.reports.admissions.excel',
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
            'laporan-penerimaan-2027-2028.xlsx',
            (string) $response->headers->get(
                'content-disposition'
            )
        );
    }

    public function test_admission_export_requires_valid_period(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route(
                    'admin.reports.admissions.excel',
                    [
                        'period_id' => 999999,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_admission_export_excludes_reenrolled_status(): void
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
            'REENROLLED',
            'TEST SUDAH DAFTAR ULANG'
        );

        $export = new \App\Exports\AdmissionsExport(
            $period
        );

        $rows = $export->collection();

        $this->assertCount(
            0,
            $rows
        );
    }

    public function test_admission_export_excludes_registered_status(): void
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
            'TEST MASIH TERDAFTAR'
        );

        $export = new \App\Exports\AdmissionsExport(
            $period
        );

        $rows = $export->collection();

        $this->assertCount(
            0,
            $rows
        );
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK ADMISSION EXPORT TEST',
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

            'registration_number' =>
                'ADM-EXPORT-'.$sequence,

            'nik' =>
                '3371717171'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' => $name,

            'origin_school' =>
                'SMP ADMISSION EXPORT TEST',

            'whatsapp' =>
                '08127171'
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