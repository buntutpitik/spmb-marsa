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

class AdminOriginSchoolRecapPdfTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_download_origin_school_recap_pdf(): void
    {
        [
            ,
            $period,
        ] = $this->makeFixture();

        $this->get(
            route(
                'admin.reports.origin-school-recap.pdf',
                [
                    'period_id' => $period->id,
                ]
            )
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_download_origin_school_recap_pdf(): void
    {
        [
            $user,
            $period,
        ] = $this->makeFixture();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.reports.origin-school-recap.pdf',
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
            'rekap-asal-sekolah-2027-2028.pdf',
            (string) $response->headers->get(
                'content-disposition'
            )
        );
    }

    public function test_origin_school_recap_pdf_groups_same_school(): void
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

        $rows = Registration::query()
            ->where(
                'period_id',
                $period->id
            )
            ->whereNotNull('origin_school')
            ->whereRaw("TRIM(origin_school) <> ''")
            ->select('origin_school')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('origin_school')
            ->get();

        $this->assertCount(
            1,
            $rows
        );

        $this->assertSame(
            'SMP MAARIF TEST',
            $rows->first()->origin_school
        );

        $this->assertSame(
            2,
            (int) $rows->first()->total
        );
    }

    public function test_origin_school_recap_pdf_excludes_empty_origin_school(): void
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

        $rows = Registration::query()
            ->where(
                'period_id',
                $period->id
            )
            ->whereNotNull('origin_school')
            ->whereRaw("TRIM(origin_school) <> ''")
            ->get();

        $this->assertCount(
            1,
            $rows
        );

        $this->assertSame(
            'SMP VALID',
            $rows->first()->origin_school
        );
    }

    public function test_origin_school_recap_pdf_requires_valid_period(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route(
                    'admin.reports.origin-school-recap.pdf',
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
            'name' => 'SMK ORIGIN PDF TEST',
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
                'ORIGIN-PDF-'.$sequence,

            'nik' =>
                '3383838383'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                'ORIGIN PDF '.$sequence,

            'origin_school' => $originSchool,

            'whatsapp' =>
                '08128383'
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