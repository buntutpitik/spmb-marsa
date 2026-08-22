<?php

namespace Tests\Feature;

use App\Exports\ReferralRecapExport;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminReferralRecapExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_export_referral_recap(): void
    {
        [
            ,
            $period,
        ] = $this->makeFixture();

        $this->get(
            route(
                'admin.reports.referral-recap.excel',
                [
                    'period_id' => $period->id,
                ]
            )
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_download_referral_recap_excel(): void
    {
        [
            $user,
            $period,
        ] = $this->makeFixture();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.reports.referral-recap.excel',
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
            'rekap-referral-pembawa-2027-2028.xlsx',
            (string) $response->headers->get(
                'content-disposition'
            )
        );
    }

    public function test_referral_recap_groups_same_name_and_source(): void
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

        $export = new ReferralRecapExport(
            $period
        );

        $rows = $export->collection();

        /*
         * PAK AHMAD / GURU
         * TOTAL
         */
        $this->assertCount(
            2,
            $rows
        );

        $referralRow = $rows->first();

        $this->assertSame(
            'PAK AHMAD',
            $referralRow->referrer_name_label
        );

        $this->assertSame(
            'GURU',
            $referralRow->referrer_source_label
        );

        $this->assertSame(
            2,
            (int) $referralRow->total
        );

        $this->assertSame(
            1,
            (int) $referralRow->registered
        );

        $this->assertSame(
            1,
            (int) $referralRow->accepted
        );
    }

    public function test_same_name_with_different_source_is_separated(): void
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
            'PAK BUDI',
            'GURU',
            'REGISTERED'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'PAK BUDI',
            'ALUMNI',
            'REGISTERED'
        );

        $export = new ReferralRecapExport(
            $period
        );

        $rows = $export
            ->collection()
            ->filter(
                fn ($row) =>
                    $row->referrer_name_label
                    !== 'TOTAL'
            );

        $this->assertCount(
            2,
            $rows
        );
    }

    public function test_referral_recap_excludes_empty_referral(): void
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

        $export = new ReferralRecapExport(
            $period
        );

        $rows = $export->collection();

        /*
         * PAK VALID / GURU
         * TOTAL
         */
        $this->assertCount(
            2,
            $rows
        );

        $this->assertSame(
            'PAK VALID',
            $rows->first()->referrer_name_label
        );
    }

    public function test_referral_recap_total_only_counts_referral_records(): void
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
            'PAK A',
            'GURU',
            'REGISTERED'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'PAK B',
            'ALUMNI',
            'REENROLLED'
        );

        $export = new ReferralRecapExport(
            $period
        );

        $rows = $export->collection();

        $totalRow = $rows->last();

        $this->assertSame(
            'TOTAL',
            $totalRow->referrer_name_label
        );

        $this->assertSame(
            2,
            (int) $totalRow->total
        );

        $this->assertSame(
            1,
            (int) $totalRow->registered
        );

        $this->assertSame(
            1,
            (int) $totalRow->reenrolled
        );
    }

    public function test_referral_recap_only_uses_selected_period(): void
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
            'PEMBAWA AKTIF',
            'GURU',
            'REGISTERED'
        );

        $otherSchool = School::create([
            'name' => 'SMK OTHER REFERRAL EXPORT',
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
            'PEMBAWA PERIODE LAIN',
            'ALUMNI',
            'REGISTERED'
        );

        $export = new ReferralRecapExport(
            $period
        );

        $rows = $export->collection();

        $this->assertCount(
            2,
            $rows
        );

        $this->assertSame(
            'PEMBAWA AKTIF',
            $rows->first()->referrer_name_label
        );
    }

    public function test_referral_recap_requires_valid_period(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route(
                    'admin.reports.referral-recap.excel',
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
            'name' => 'SMK REFERRAL EXPORT TEST',
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
            'wave_id' => null,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,

            'registration_number' =>
                'REF-EXPORT-'.$sequence,

            'nik' =>
                '3376767676'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                'REFERRAL EXPORT '.$sequence,

            'origin_school' => 'SMP TEST',

            'referrer_name' =>
                $referrerName,

            'referrer_source' =>
                $referrerSource,

            'whatsapp' =>
                '08127676'
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