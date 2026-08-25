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

class AdminReferralRecapTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_referral_recap(): void
    {
        $this->get(
            route('admin.recaps.referrals.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_access_referral_recap(): void
    {
        [$user] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route('admin.recaps.referrals.index')
            )
            ->assertOk()
            ->assertSee('Rekap Referral / Pembawa')
            ->assertSee('Distribusi Referral');
    }

    public function test_referral_recap_groups_same_name_and_source(): void
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
            'PAK AHMAD',
            'ALUMNI',
            'REGISTERED'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'PAK AHMAD',
            'ALUMNI',
            'ACCEPTED'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.recaps.referrals.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('PAK AHMAD')
            ->assertSee('ALUMNI');

        $this->assertSame(
            1,
            substr_count(
                $response->getContent(),
                'PAK AHMAD'
            )
        );
    }

    public function test_same_referrer_name_with_different_source_is_separated(): void
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

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.recaps.referrals.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('GURU')
            ->assertSee('ALUMNI');

        $this->assertSame(
            2,
            substr_count(
                $response->getContent(),
                'PAK BUDI'
            )
        );
    }

    public function test_referral_recap_uses_selected_period_only(): void
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
            'PEMBAWA AKTIF',
            'GURU',
            'REGISTERED'
        );

        $otherSchool = School::create([
            'name' => 'SMK REFERRAL OTHER',
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
            'is_active' => true,
        ]);

        $otherMajor = Major::create([
            'school_id' => $otherSchool->id,
            'code' => 'OTH',
            'name' => 'OTHER',
            'is_active' => true,
        ]);

        $this->makeRegistration(
            $otherPeriod,
            $otherPath,
            $otherMajor,
            'PEMBAWA PERIODE LAIN',
            'ALUMNI',
            'REGISTERED'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.recaps.referrals.index',
                    [
                        'period_id' => $period->id,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('PEMBAWA AKTIF')
            ->assertDontSee('PEMBAWA PERIODE LAIN');
    }

    public function test_referral_recap_can_search_name_or_source(): void
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
            'PAK ALPHA',
            'GURU',
            'REGISTERED'
        );

        $this->makeRegistration(
            $period,
            $path,
            $major,
            'IBU BETA',
            'ALUMNI',
            'REGISTERED'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.recaps.referrals.index',
                    [
                        'period_id' => $period->id,
                        'q' => 'ALPHA',
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('PAK ALPHA')
            ->assertDontSee('IBU BETA');
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK REFERRAL TEST',
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
                'REFERRAL-TEST-'.$sequence,

            'nik' =>
                '3366666666'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                'REFERRAL TEST '.$sequence,

            'origin_school' => 'SMP TEST',

            'referrer_name' => $referrerName,
            'referrer_source' => $referrerSource,

            'whatsapp' =>
                '08126666'
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

            'rejected_at' =>
                $status === 'REJECTED'
                    ? now()
                    : null,

            'reenrolled_at' =>
                $status === 'REENROLLED'
                    ? now()
                    : null,

            'withdrawn_at' =>
                $status === 'WITHDRAWN'
                    ? now()
                    : null,
        ]);
    }
}