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

class AdminReferralRecapPdfTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_download_referral_recap_pdf(): void
    {
        [
            ,
            $period,
        ] = $this->makeFixture();

        $this->get(
            route(
                'admin.reports.referral-recap.pdf',
                [
                    'period_id' => $period->id,
                ]
            )
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_download_referral_recap_pdf(): void
    {
        [
            $user,
            $period,
        ] = $this->makeFixture();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.reports.referral-recap.pdf',
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
            'rekap-referral-pembawa-2027-2028.pdf',
            (string) $response->headers->get(
                'content-disposition'
            )
        );
    }

    public function test_referral_recap_pdf_groups_same_name_and_source(): void
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
            'PAK AHMAD',
            'GURU',
            'REGISTERED'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'PAK AHMAD',
            'GURU',
            'ACCEPTED'
        );

        $rows = Registration::query()
            ->where(
                'period_id',
                $period->id
            )
            ->where(function ($query) {
                $query
                    ->whereNotNull('referrer_name')
                    ->orWhereNotNull('referrer_source');
            })
            ->select([
                'referrer_name',
                'referrer_source',
            ])
            ->selectRaw(
                'COUNT(*) as total'
            )
            ->groupBy(
                'referrer_name',
                'referrer_source'
            )
            ->get();

        $this->assertCount(
            1,
            $rows
        );

        $this->assertSame(
            2,
            (int) $rows->first()->total
        );
    }

    public function test_referral_recap_pdf_excludes_empty_referral(): void
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
            null,
            'REGISTERED'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'PAK VALID',
            'GURU',
            'REGISTERED'
        );

        $rows = Registration::query()
            ->where(
                'period_id',
                $period->id
            )
            ->where(function ($query) {
                $query
                    ->where(function ($subQuery) {
                        $subQuery
                            ->whereNotNull('referrer_name')
                            ->whereRaw(
                                "TRIM(referrer_name) <> ''"
                            );
                    })
                    ->orWhere(function ($subQuery) {
                        $subQuery
                            ->whereNotNull('referrer_source')
                            ->whereRaw(
                                "TRIM(referrer_source) <> ''"
                            );
                    });
            })
            ->get();

        $this->assertCount(
            1,
            $rows
        );

        $this->assertSame(
            'PAK VALID',
            $rows->first()->referrer_name
        );
    }

    public function test_referral_recap_pdf_requires_valid_period(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route(
                    'admin.reports.referral-recap.pdf',
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
            'name' => 'SMK REFERRAL PDF TEST',
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
        ?string $referrerName,
        ?string $referrerSource,
        string $status
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,

            'registration_number' =>
                'REF-PDF-'.$sequence,

            'nik' =>
                '3384848484'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                'REFERRAL PDF '.$sequence,

            'origin_school' => 'SMP TEST',

            'referrer_name' =>
                $referrerName,

            'referrer_source' =>
                $referrerSource,

            'whatsapp' =>
                '08128484'
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