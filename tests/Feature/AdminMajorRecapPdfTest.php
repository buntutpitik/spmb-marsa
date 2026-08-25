<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PeriodMajor;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminMajorRecapPdfTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_download_major_recap_pdf(): void
    {
        [
            ,
            $period,
        ] = $this->makeFixture();

        $this->get(
            route(
                'admin.reports.major-recap.pdf',
                [
                    'period_id' => $period->id,
                ]
            )
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_download_major_recap_pdf(): void
    {
        [
            $user,
            $period,
        ] = $this->makeFixture();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.reports.major-recap.pdf',
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
            'rekap-jurusan-2027-2028.pdf',
            (string) $response->headers->get(
                'content-disposition'
            )
        );
    }

    public function test_major_recap_pdf_counts_gender_and_status(): void
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

        $rows = Registration::query()
            ->where(
                'period_id',
                $period->id
            )
            ->where(
                'major_id',
                $major->id
            )
            ->get();

        $this->assertCount(
            3,
            $rows
        );

        $this->assertSame(
            2,
            $rows->where('gender', 'L')->count()
        );

        $this->assertSame(
            1,
            $rows->where('gender', 'P')->count()
        );

        $this->assertSame(
            1,
            $rows->where(
                'status',
                'REENROLLED'
            )->count()
        );
    }

    public function test_major_recap_pdf_requires_valid_period(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route(
                    'admin.reports.major-recap.pdf',
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
            'name' => 'SMK MAJOR PDF TEST',
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
                'MAJOR-PDF-'.$sequence,

            'nik' =>
                '3382828282'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                'MAJOR PDF '.$sequence,

            'gender' => $gender,

            'origin_school' => 'SMP TEST',

            'whatsapp' =>
                '08128282'
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