<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_analytics_page(): void
    {
        $this->get(
            route('admin.analytics.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_access_analytics_page(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route('admin.analytics.index')
            )
            ->assertOk()
            ->assertSee('Analitik SPMB')
            ->assertSee('Tren Pendaftaran')
            ->assertSee('Distribusi Status');
    }

    public function test_analytics_only_uses_selected_period(): void
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
            'PENDAFTAR PERIODE AKTIF',
            '2027-01-10 08:00:00'
        );

        $otherSchool = School::create([
            'name' => 'SMK ANALYTICS OTHER',
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
            'REGISTERED',
            'PENDAFTAR PERIODE LAIN',
            '2028-01-10 08:00:00'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.analytics.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response->assertOk();

        $summary = $response->viewData('summary');

        $this->assertSame(
            1,
            $summary['TOTAL']
        );

        $this->assertSame(
            1,
            $summary['REGISTERED']
        );

        $dailyTrend = $response->viewData(
            'dailyTrend'
        );

        $this->assertCount(
            1,
            $dailyTrend
        );

        $this->assertSame(
            '2027-01-10',
            (string) $dailyTrend[0]->registration_date
        );

        $majorDistribution = $response->viewData(
            'majorDistribution'
        );

        $this->assertCount(
            1,
            $majorDistribution
        );

        $this->assertSame(
            'TST',
            $majorDistribution[0]->code
        );
    }

    public function test_analytics_counts_daily_registration_trend(): void
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
            'TREND ONE',
            '2027-01-10 08:00:00'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'ACCEPTED',
            'TREND TWO',
            '2027-01-10 09:00:00'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'REENROLLED',
            'TREND THREE',
            '2027-01-11 09:00:00'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.analytics.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response->assertOk();

        $viewData = $response->viewData(
            'dailyTrend'
        );

        $this->assertCount(
            2,
            $viewData
        );

        $this->assertSame(
            2,
            (int) $viewData[0]->total
        );

        $this->assertSame(
            1,
            (int) $viewData[1]->total
        );
    }

    public function test_analytics_excludes_empty_referral_from_top_referrals(): void
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
            'NO REFERRAL',
            '2027-01-10 08:00:00',
            null,
            null
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'REGISTERED',
            'WITH REFERRAL',
            '2027-01-10 09:00:00',
            'PAK AHMAD',
            'GURU'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.analytics.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response->assertOk();

        $topReferrals = $response->viewData(
            'topReferrals'
        );

        $this->assertCount(
            1,
            $topReferrals
        );

        $this->assertSame(
            'PAK AHMAD',
            $topReferrals[0]->referrer_name_label
        );
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK ANALYTICS TEST',
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
        string $name,
        string $registeredAt,
        ?string $referrerName = null,
        ?string $referrerSource = null
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,

            'registration_number' =>
                'ANALYTICS-'.$sequence,

            'nik' =>
                '3399999999'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' => $name,

            'origin_school' =>
                'SMP ANALYTICS TEST',

            'referrer_name' =>
                $referrerName,

            'referrer_source' =>
                $referrerSource,

            'whatsapp' =>
                '08129999'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'data_source' => 'ADMIN',
            'status' => $status,

            'registered_at' =>
                Carbon::parse($registeredAt),

            'accepted_at' =>
                in_array(
                    $status,
                    ['ACCEPTED', 'REENROLLED'],
                    true
                )
                    ? Carbon::parse($registeredAt)
                    : null,

            'reenrolled_at' =>
                $status === 'REENROLLED'
                    ? Carbon::parse($registeredAt)
                    : null,
        ]);
    }
}