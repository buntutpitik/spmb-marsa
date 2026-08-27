<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use App\Models\AdmissionPath;
use App\Models\Major;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPeriodComparisonTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private PpdbPeriod $periodA;

    private PpdbPeriod $periodB;

    private Major $major;

    private AdmissionPath $pathA;

    private AdmissionPath $pathB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'name' => 'SMK COMPARISON TEST',
        ]);

        $this->periodA = $this->makePeriod(
            '2026/2027',
            2026,
            false,
            'CLOSED'
        );

        $this->periodB = $this->makePeriod(
            '2027/2028',
            2027,
            true,
            'OPEN'
        );

        $this->major = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'is_active' => true,
        ]);

        $this->pathA = $this->makePath(
            $this->periodA,
            'KHUSUS-A'
        );

        $this->pathB = $this->makePath(
            $this->periodB,
            'UMUM-B'
        );
    }

    public function test_admin_can_open_period_comparison_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index'))
            ->assertOk()
            ->assertSee('Perbandingan Antar Tahun');
    }

    public function test_guest_cannot_open_period_comparison_page(): void
    {
        $this->get(route('admin.comparison.index'))
            ->assertRedirect(route('login'));
    }

    public function test_comparison_page_lists_available_periods(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index'))
            ->assertOk()
            ->assertSee('2026/2027')
            ->assertSee('2027/2028');
    }

    public function test_explicit_period_a_and_b_are_used(): void
    {
        $this->makeRegistrations(
            $this->periodA,
            3,
            'A'
        );

        $this->makeRegistrations(
            $this->periodB,
            5,
            'B'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('3')
            ->assertSee('5');
    }

    public function test_comparison_calculates_total_delta(): void
    {
        $this->makeRegistrations(
            $this->periodA,
            3,
            'A'
        );

        $this->makeRegistrations(
            $this->periodB,
            5,
            'B'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('+2');
    }

    public function test_comparison_calculates_growth_percentage(): void
    {
        $this->makeRegistrations(
            $this->periodA,
            4,
            'A'
        );

        $this->makeRegistrations(
            $this->periodB,
            6,
            'B'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('50,0%');
    }

    public function test_zero_baseline_does_not_divide_by_zero(): void
    {
        $this->makeRegistrations(
            $this->periodB,
            5,
            'B'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('Tidak tersedia');
    }

    public function test_same_period_cannot_be_compared_with_itself(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodA->id,
            ]))
            ->assertStatus(422);
    }

    public function test_default_comparison_uses_latest_historical_and_active_period(): void
    {
        $this->makeRegistrations(
            $this->periodA,
            3,
            'A'
        );

        $this->makeRegistrations(
            $this->periodB,
            5,
            'B'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index'))
            ->assertOk()
            ->assertSee('2026/2027')
            ->assertSee('2027/2028')
            ->assertSee('3')
            ->assertSee('5');
    }

    public function test_comparison_includes_status_breakdown(): void
    {
        $this->makeRegistrationsWithStatus(
            $this->periodA,
            [
                'REGISTERED' => 2,
                'ACCEPTED' => 3,
                'REJECTED' => 1,
                'REENROLLED' => 4,
                'WITHDRAWN' => 0,
            ],
            'SA'
        );

        $this->makeRegistrationsWithStatus(
            $this->periodB,
            [
                'REGISTERED' => 1,
                'ACCEPTED' => 4,
                'REJECTED' => 2,
                'REENROLLED' => 6,
                'WITHDRAWN' => 1,
            ],
            'SB'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('Status')
            ->assertSee('Terdaftar')
            ->assertSee('Diterima')
            ->assertSee('Ditolak')
            ->assertSee('Daftar Ulang')
            ->assertSee('Mengundurkan Diri');
    }

    public function test_status_breakdown_calculates_counts_deltas_and_shares(): void
    {
        $this->makeRegistrationsWithStatus(
            $this->periodA,
            [
                'REGISTERED' => 2,
                'ACCEPTED' => 3,
                'REJECTED' => 1,
                'REENROLLED' => 4,
                'WITHDRAWN' => 0,
            ],
            'SA'
        );

        $this->makeRegistrationsWithStatus(
            $this->periodB,
            [
                'REGISTERED' => 1,
                'ACCEPTED' => 4,
                'REJECTED' => 2,
                'REENROLLED' => 6,
                'WITHDRAWN' => 1,
            ],
            'SB'
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $response
            ->assertOk()

            // REGISTERED: 2/10 = 20.0%, 1/14 = 7.1%
            ->assertSee('Terdaftar')
            ->assertSee('20,0%')
            ->assertSee('7,1%')
            ->assertSee('-12,9')

            // ACCEPTED: 3/10 = 30.0%, 4/14 = 28.6%
            ->assertSee('Diterima')
            ->assertSee('30,0%')
            ->assertSee('28,6%')
            ->assertSee('-1,4')

            // REENROLLED: 4/10 = 40.0%, 6/14 = 42.9%
            ->assertSee('Daftar Ulang')
            ->assertSee('40,0%')
            ->assertSee('42,9%')
            ->assertSee('+2,9');
    }

    public function test_comparison_includes_major_breakdown_using_union_of_both_periods(): void
    {
        $majorAOnly = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Teknik Kendaraan Ringan Otomotif',
            'code' => 'TKRO',
            'is_active' => true,
        ]);

        $majorBoth = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak 2',
            'code' => 'RPL2',
            'is_active' => true,
        ]);

        $majorBOnly = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Kuliner',
            'code' => 'KUL',
            'is_active' => true,
        ]);

        $this->makeRegistrationsForMajor(
            $this->periodA,
            $majorAOnly,
            3,
            'MA'
        );

        $this->makeRegistrationsForMajor(
            $this->periodA,
            $majorBoth,
            2,
            'MB'
        );

        $this->makeRegistrationsForMajor(
            $this->periodB,
            $majorBoth,
            4,
            'MC'
        );

        $this->makeRegistrationsForMajor(
            $this->periodB,
            $majorBOnly,
            5,
            'MD'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('Jurusan')
            ->assertSee('TKRO')
            ->assertSee('RPL2')
            ->assertSee('KUL');
    }

    public function test_major_breakdown_calculates_counts_deltas_and_shares(): void
    {
        $majorAOnly = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Teknik Kendaraan Ringan Otomotif',
            'code' => 'TKRO',
            'is_active' => true,
        ]);

        $majorBoth = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak 2',
            'code' => 'RPL2',
            'is_active' => true,
        ]);

        $majorBOnly = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Kuliner',
            'code' => 'KUL',
            'is_active' => true,
        ]);

        $this->makeRegistrationsForMajor(
            $this->periodA,
            $majorAOnly,
            3,
            'MNA'
        );

        $this->makeRegistrationsForMajor(
            $this->periodA,
            $majorBoth,
            2,
            'MNB'
        );

        $this->makeRegistrationsForMajor(
            $this->periodB,
            $majorBoth,
            4,
            'MNC'
        );

        $this->makeRegistrationsForMajor(
            $this->periodB,
            $majorBOnly,
            5,
            'MND'
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $response
            ->assertOk()

            // TKRO: 3/5 = 60,0%, 0/9 = 0,0%
            ->assertSee('TKRO')
            ->assertSee('60,0%')
            ->assertSee('-60,0')

            // RPL2: 2/5 = 40,0%, 4/9 = 44,4%
            ->assertSee('RPL2')
            ->assertSee('40,0%')
            ->assertSee('44,4%')
            ->assertSee('+4,4')

            // KUL: 0/5 = 0,0%, 5/9 = 55,6%
            ->assertSee('KUL')
            ->assertSee('55,6%')
            ->assertSee('+55,6');
    }

    public function test_comparison_includes_gender_breakdown(): void
    {
        $this->makeRegistrationsForGender(
            $this->periodA,
            [
                'L' => 6,
                'P' => 4,
            ],
            'GA'
        );

        $this->makeRegistrationsForGender(
            $this->periodB,
            [
                'L' => 7,
                'P' => 7,
            ],
            'GB'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('Gender')
            ->assertSee('Laki-laki')
            ->assertSee('Perempuan');
    }

    public function test_gender_breakdown_calculates_counts_deltas_and_shares(): void
    {
        $this->makeRegistrationsForGender(
            $this->periodA,
            [
                'L' => 6,
                'P' => 4,
            ],
            'GA'
        );

        $this->makeRegistrationsForGender(
            $this->periodB,
            [
                'L' => 7,
                'P' => 7,
            ],
            'GB'
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $response
            ->assertOk()

            // Laki-laki: 6/10 = 60,0%, 7/14 = 50,0%
            ->assertSee('Laki-laki')
            ->assertSee('60,0%')
            ->assertSee('50,0%')
            ->assertSee('-10,0')

            // Perempuan: 4/10 = 40,0%, 7/14 = 50,0%
            ->assertSee('Perempuan')
            ->assertSee('40,0%')
            ->assertSee('+10,0');
    }

    public function test_comparison_includes_admission_path_breakdown_using_union_of_both_periods(): void
    {
        $pathAOnly = $this->makePath(
            $this->periodA,
            'KHUSUS-A-ONLY'
        );

        $pathBothA = $this->makePath(
            $this->periodA,
            'UMUM-SHARED'
        );

        $pathBothB = $this->makePath(
            $this->periodB,
            'UMUM-SHARED'
        );

        $pathBOnly = $this->makePath(
            $this->periodB,
            'KHUSUS-B-ONLY'
        );

        $this->makeRegistrationsForPath(
            $this->periodA,
            $pathAOnly,
            3,
            'PA'
        );

        $this->makeRegistrationsForPath(
            $this->periodA,
            $pathBothA,
            2,
            'PB'
        );

        $this->makeRegistrationsForPath(
            $this->periodB,
            $pathBothB,
            4,
            'PC'
        );

        $this->makeRegistrationsForPath(
            $this->periodB,
            $pathBOnly,
            5,
            'PD'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('Jalur Pendaftaran')
            ->assertSee('KHUSUS-A-ONLY')
            ->assertSee('UMUM-SHARED')
            ->assertSee('KHUSUS-B-ONLY');
    }

    public function test_admission_path_breakdown_calculates_counts_deltas_and_shares(): void
    {
        $pathAOnly = $this->makePath(
            $this->periodA,
            'KHUSUS-A-NUM'
        );

        $pathBothA = $this->makePath(
            $this->periodA,
            'UMUM-NUM'
        );

        $pathBothB = $this->makePath(
            $this->periodB,
            'UMUM-NUM'
        );

        $pathBOnly = $this->makePath(
            $this->periodB,
            'KHUSUS-B-NUM'
        );

        $this->makeRegistrationsForPath(
            $this->periodA,
            $pathAOnly,
            3,
            'PNA'
        );

        $this->makeRegistrationsForPath(
            $this->periodA,
            $pathBothA,
            2,
            'PNB'
        );

        $this->makeRegistrationsForPath(
            $this->periodB,
            $pathBothB,
            4,
            'PNC'
        );

        $this->makeRegistrationsForPath(
            $this->periodB,
            $pathBOnly,
            5,
            'PND'
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $response
            ->assertOk()

            // A-only: 3/5 = 60,0%, 0/9 = 0,0%
            ->assertSee('KHUSUS-A-NUM')
            ->assertSee('60,0%')
            ->assertSee('-60,0')

            // Shared: 2/5 = 40,0%, 4/9 = 44,4%
            ->assertSee('UMUM-NUM')
            ->assertSee('40,0%')
            ->assertSee('44,4%')
            ->assertSee('+4,4')

            // B-only: 0/5 = 0,0%, 5/9 = 55,6%
            ->assertSee('KHUSUS-B-NUM')
            ->assertSee('55,6%')
            ->assertSee('+55,6');
    }

    public function test_comparison_includes_data_source_breakdown(): void
    {
        $this->makeRegistrationsForDataSource(
            $this->periodA,
            [
                'PUBLIC' => 3,
                'ADMIN' => 7,
            ],
            'DA'
        );

        $this->makeRegistrationsForDataSource(
            $this->periodB,
            [
                'PUBLIC' => 8,
                'ADMIN' => 6,
            ],
            'DB'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('Asal Data Pendaftaran')
            ->assertSee('Pendaftaran Mandiri')
            ->assertSee('Input Panitia')
            ->assertDontSee('>PUBLIC<', false)
            ->assertDontSee('>ADMIN<', false);
    }

    public function test_data_source_breakdown_and_self_service_rate_are_calculated(): void
    {
        $this->makeRegistrationsForDataSource(
            $this->periodA,
            [
                'PUBLIC' => 3,
                'ADMIN' => 7,
            ],
            'DSA'
        );

        $this->makeRegistrationsForDataSource(
            $this->periodB,
            [
                'PUBLIC' => 8,
                'ADMIN' => 6,
            ],
            'DSB'
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $response
            ->assertOk()

            // PUBLIC: 3/10 = 30,0%, 8/14 = 57,1%
            ->assertSee('Pendaftaran Mandiri')
            ->assertSee('30,0%')
            ->assertSee('57,1%')
            ->assertSee('+27,1')

            // ADMIN: 7/10 = 70,0%, 6/14 = 42,9%
            ->assertSee('Input Panitia')
            ->assertSee('70,0%')
            ->assertSee('42,9%')
            ->assertSee('-27,1')

            ->assertSee('Self-Service Registration Rate');
    }

    public function test_comparison_includes_origin_school_breakdown(): void
    {
        $this->makeRegistrationsForOriginSchool(
            $this->periodA,
            [
                'SMP NEGERI 1 KEBUMEN' => 3,
                'SMP NEGERI 2 KEBUMEN' => 2,
            ],
            'OSA'
        );

        $this->makeRegistrationsForOriginSchool(
            $this->periodB,
            [
                'SMP NEGERI 1 KEBUMEN' => 4,
                'SMP NEGERI 3 KEBUMEN' => 5,
            ],
            'OSB'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('Sekolah Asal')
            ->assertSee('SMP NEGERI 1 KEBUMEN')
            ->assertSee('SMP NEGERI 2 KEBUMEN')
            ->assertSee('SMP NEGERI 3 KEBUMEN');
    }

    public function test_origin_school_breakdown_normalizes_names_and_calculates_counts(): void
    {
        $this->makeRegistrationsForOriginSchool(
            $this->periodA,
            [
                'SMP NEGERI 1 KEBUMEN' => 3,
                'SMP NEGERI 2 KEBUMEN' => 2,
            ],
            'OSNA'
        );

        $this->makeRegistrationsForOriginSchool(
            $this->periodB,
            [
                '  smp   negeri 1 kebumen  ' => 4,
                'SMP NEGERI 3 KEBUMEN' => 5,
            ],
            'OSNB'
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $response
            ->assertOk()

            // SMP 1: 3/5 = 60%, 4/9 = 44,4%
            ->assertSee('SMP NEGERI 1 KEBUMEN')
            ->assertSee('60,0%')
            ->assertSee('44,4%')
            ->assertSee('-15,6')

            // SMP 2: only A
            ->assertSee('SMP NEGERI 2 KEBUMEN')

            // SMP 3: only B
            ->assertSee('SMP NEGERI 3 KEBUMEN')
            ->assertSee('55,6%');
    }

    public function test_comparison_includes_referral_breakdown_and_excludes_empty_values(): void
    {
        $this->makeRegistrationsForReferral(
            $this->periodA,
            [
                'PAK BUDI' => 3,
                'BU SITI' => 2,
                null => 2,
                '' => 1,
            ],
            'RA'
        );

        $this->makeRegistrationsForReferral(
            $this->periodB,
            [
                'PAK BUDI' => 4,
                'PAK ANDI' => 5,
                '   ' => 2,
            ],
            'RB'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('Referral')
            ->assertSee('PAK BUDI')
            ->assertSee('BU SITI')
            ->assertSee('PAK ANDI');
    }

    public function test_referral_breakdown_normalizes_names_and_calculates_counts_and_shares(): void
    {
        $this->makeRegistrationsForReferral(
            $this->periodA,
            [
                'PAK BUDI' => 3,
                'BU SITI' => 2,
            ],
            'RNA'
        );

        $this->makeRegistrationsForReferral(
            $this->periodB,
            [
                '  pak   budi  ' => 4,
                'PAK ANDI' => 5,
            ],
            'RNB'
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $response
            ->assertOk()

            // PAK BUDI: 3/5 = 60,0%, 4/9 = 44,4%
            ->assertSee('PAK BUDI')
            ->assertSee('60,0%')
            ->assertSee('44,4%')
            ->assertSee('-15,6')

            // BU SITI hanya A
            ->assertSee('BU SITI')

            // PAK ANDI hanya B
            ->assertSee('PAK ANDI')
            ->assertSee('55,6%');
    }

    public function test_comparison_includes_monthly_registration_trend(): void
    {
        $this->makeRegistrationAt(
            $this->periodA,
            '2026-01-10 08:00:00',
            'TA1'
        );

        $this->makeRegistrationAt(
            $this->periodA,
            '2026-03-10 08:00:00',
            'TA2'
        );

        $this->makeRegistrationAt(
            $this->periodB,
            '2027-01-10 08:00:00',
            'TB1'
        );

        $this->makeRegistrationAt(
            $this->periodB,
            '2027-02-10 08:00:00',
            'TB2'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('Tren Pendaftaran')
            ->assertSee('Januari')
            ->assertSee('Februari')
            ->assertSee('Maret')
            ->assertSee('Desember');
    }

    public function test_monthly_registration_trend_keeps_zero_months_and_calculates_counts(): void
    {
        $this->makeRegistrationAt(
            $this->periodA,
            '2026-01-05 08:00:00',
            'TNA1'
        );

        $this->makeRegistrationAt(
            $this->periodA,
            '2026-01-20 08:00:00',
            'TNA2'
        );

        $this->makeRegistrationAt(
            $this->periodA,
            '2026-03-15 08:00:00',
            'TNA3'
        );

        $this->makeRegistrationAt(
            $this->periodB,
            '2027-01-05 08:00:00',
            'TNB1'
        );

        $this->makeRegistrationAt(
            $this->periodB,
            '2027-02-10 08:00:00',
            'TNB2'
        );

        $this->makeRegistrationAt(
            $this->periodB,
            '2027-02-20 08:00:00',
            'TNB3'
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $response
            ->assertOk()

            // Januari: A=2, B=1, delta=-1
            ->assertSee('Januari')

            // Februari: A=0, B=2, delta=+2
            ->assertSee('Februari')

            // Maret: A=1, B=0, delta=-1
            ->assertSee('Maret')

            // Bulan tanpa data tetap muncul
            ->assertSee('April')
            ->assertSee('Desember');
    }

    public function test_comparison_includes_reenrollment_and_finance_summary(): void
    {
        $this->makeReenrollmentFinanceData(
            $this->periodA,
            [
                100000,
                150000,
            ],
            'FA'
        );

        $this->makeReenrollmentFinanceData(
            $this->periodB,
            [
                100000,
                100000,
                200000,
            ],
            'FB'
        );

        $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]))
            ->assertOk()
            ->assertSee('Daftar Ulang & Keuangan', false)
            ->assertSee('Jumlah Daftar Ulang')
            ->assertSee('Jumlah Transaksi')
            ->assertSee('Total Pembayaran');
    }

    public function test_reenrollment_and_finance_summary_calculates_totals_and_deltas(): void
    {
        $this->makeReenrollmentFinanceData(
            $this->periodA,
            [
                100000,
                150000,
            ],
            'FNA'
        );

        $this->makeReenrollmentFinanceData(
            $this->periodB,
            [
                100000,
                100000,
                200000,
            ],
            'FNB'
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $response
            ->assertOk()

            // A = 2 transaksi, Rp250.000
            ->assertSee('250.000')

            // B = 3 transaksi, Rp400.000
            ->assertSee('400.000')

            // Delta pembayaran = +Rp150.000
            ->assertSee('150.000');
    }

    public function test_reenrollment_finance_preserves_historical_anomalies(): void
    {
        $pathA = $this->pathA;
        $pathB = $this->pathB;

        // Historical A:
        // 2 daftar ulang, tetapi hanya 1 yang punya payment.
        $historicalReenrolledWithoutPayment = $this->makeFinanceRegistration(
            $this->periodA,
            $pathA,
            'HIST-NOPAY',
            'REENROLLED'
        );

        $historicalReenrolledWithPayment = $this->makeFinanceRegistration(
            $this->periodA,
            $pathA,
            'HIST-PAY',
            'REENROLLED'
        );

        \App\Models\ReenrollmentPayment::query()->create([
            'registration_id' => $historicalReenrolledWithPayment->id,
            'payment_date' => now(),
            'amount' => 250000,
            'recorded_by' => $this->admin->id,
            'payment_method' => 'CASH',
        ]);

        // Historical anomaly:
        // REJECTED tetapi memiliki payment.
        $historicalRejectedWithPayment = $this->makeFinanceRegistration(
            $this->periodA,
            $pathA,
            'HIST-REJECT-PAY',
            'REJECTED'
        );

        \App\Models\ReenrollmentPayment::query()->create([
            'registration_id' => $historicalRejectedWithPayment->id,
            'payment_date' => now(),
            'amount' => 100000,
            'recorded_by' => $this->admin->id,
            'payment_method' => 'CASH',
        ]);

        // Operational B:
        // 1 daftar ulang dan 1 payment normal.
        $activeReenrolled = $this->makeFinanceRegistration(
            $this->periodB,
            $pathB,
            'ACTIVE-PAY',
            'REENROLLED'
        );

        \App\Models\ReenrollmentPayment::query()->create([
            'registration_id' => $activeReenrolled->id,
            'payment_date' => now(),
            'amount' => 300000,
            'recorded_by' => $this->admin->id,
            'payment_method' => 'CASH',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.comparison.index', [
                'period_a' => $this->periodA->id,
                'period_b' => $this->periodB->id,
            ]));

        $response
            ->assertOk()

            // A:
            // REENROLLED = 2
            // transactions = 2
            // total payments = 350.000
            ->assertSee('350.000')

            // B:
            // REENROLLED = 1
            // transactions = 1
            // total payments = 300.000
            ->assertSee('300.000')

            // Payment delta = -50.000
            ->assertSee('50.000');
    }

    private function makePeriod(
        string $name,
        int $yearStart,
        bool $isActive,
        string $status
    ): PpdbPeriod {
        return PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => $name,
            'year_start' => $yearStart,
            'year_end' => $yearStart + 1,
            'registration_open' => $yearStart.'-01-01',
            'registration_close' => $yearStart.'-12-31',
            'status' => $status,
            'is_active' => $isActive,
            'number_prefix' => 'MARSA',
            'number_year' => $yearStart,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);
    }

    private function makePath(
        PpdbPeriod $period,
        string $code
    ): AdmissionPath {
        return AdmissionPath::query()->create([
            'period_id' => $period->id,
            'name' => $code,
            'code' => $code,
            'start_date' => $period->year_start.'-01-01',
            'end_date' => $period->year_start.'-06-30',
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    private function makeRegistrations(
        PpdbPeriod $period,
        int $count,
        string $prefix
    ): void {
        $path = $period->is($this->periodA)
            ? $this->pathA
            : $this->pathB;
        for ($i = 1; $i <= $count; $i++) {
            Registration::query()->create([
                'period_id' => $period->id,
                'admission_path_id' => $path->id,
                'major_id' => $this->major->id,
                'registration_number' =>
                    $prefix.'-'.str_pad(
                        (string) $i,
                        4,
                        '0',
                        STR_PAD_LEFT
                    ),
                'nik' =>
                    ($prefix === 'A'
                        ? '3311111111'
                        : '3322222222')
                    .str_pad(
                        (string) $i,
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),
                'full_name' =>
                    'COMPARISON '.$prefix.' '.$i,
                'whatsapp' =>
                    '0812'
                    .str_pad(
                        (string) ($prefix === 'A' ? 10000000 : 20000000) + $i,
                        8,
                        '0',
                        STR_PAD_LEFT
                    ),
                'data_source' => 'ADMIN',
                'status' => 'REGISTERED',
                'registered_at' => now(),
            ]);
        }
    }

    private function makeRegistrationsWithStatus(
        PpdbPeriod $period,
        array $statusCounts,
        string $prefix
    ): void {
        $sequence = 0;

        $path = $period->is($this->periodA)
            ? $this->pathA
            : $this->pathB;

        foreach ($statusCounts as $status => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $sequence++;

                Registration::query()->create([
                    'period_id' => $period->id,
                    'admission_path_id' => $path->id,
                    'major_id' => $this->major->id,

                    'registration_number' =>
                        $prefix.'-'.str_pad(
                            (string) $sequence,
                            4,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'nik' =>
                        ($prefix === 'SA'
                            ? '3333333333'
                            : '3344444444')
                        .str_pad(
                            (string) $sequence,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'full_name' =>
                        'STATUS COMPARISON '
                        .$prefix
                        .' '
                        .$sequence,

                    'whatsapp' =>
                        '0813'
                        .str_pad(
                            (string) (
                                ($prefix === 'SA'
                                    ? 10000000
                                    : 20000000)
                                + $sequence
                            ),
                            8,
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
    }

    private function makeRegistrationsForMajor(
        PpdbPeriod $period,
        Major $major,
        int $count,
        string $prefix
    ): void {
        $path = $period->is($this->periodA)
            ? $this->pathA
            : $this->pathB;

        for ($i = 1; $i <= $count; $i++) {
            Registration::query()->create([
                'period_id' => $period->id,
                'admission_path_id' => $path->id,
                'major_id' => $major->id,

                'registration_number' =>
                    $prefix.'-'.str_pad(
                        (string) $i,
                        4,
                        '0',
                        STR_PAD_LEFT
                    ),

                'nik' =>
                    '3355555555'
                    .str_pad(
                        (string) (
                            crc32($prefix) % 100000
                        ),
                        5,
                        '0',
                        STR_PAD_LEFT
                    )
                    .str_pad(
                        (string) $i,
                        1,
                        '0',
                        STR_PAD_LEFT
                    ),

                'full_name' =>
                    'MAJOR COMPARISON '.$prefix.' '.$i,

                'whatsapp' =>
                    '0814'
                    .str_pad(
                        (string) (
                            ($i + (crc32($prefix) % 10000000))
                        ),
                        8,
                        '0',
                        STR_PAD_LEFT
                    ),

                'data_source' => 'ADMIN',
                'status' => 'REGISTERED',
                'registered_at' => now(),
            ]);
        }
    }

    private function makeRegistrationsForGender(
        PpdbPeriod $period,
        array $genderCounts,
        string $prefix
    ): void {
        $sequence = 0;

        $path = $period->is($this->periodA)
            ? $this->pathA
            : $this->pathB;

        foreach ($genderCounts as $gender => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $sequence++;

                Registration::query()->create([
                    'period_id' => $period->id,
                    'admission_path_id' => $path->id,
                    'major_id' => $this->major->id,

                    'registration_number' =>
                        $prefix.'-'.str_pad(
                            (string) $sequence,
                            4,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'nik' =>
                        ($prefix === 'GA'
                            ? '3366666666'
                            : '3377777777')
                        .str_pad(
                            (string) $sequence,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'full_name' =>
                        'GENDER COMPARISON '
                        .$prefix
                        .' '
                        .$sequence,

                    'birth_place' => 'KEBUMEN',
                    'birth_date' => '2010-01-01',
                    'gender' => $gender,
                    'religion' => 'ISLAM',

                    'whatsapp' =>
                        '0815'
                        .str_pad(
                            (string) (
                                ($prefix === 'GA'
                                    ? 10000000
                                    : 20000000)
                                + $sequence
                            ),
                            8,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'data_source' => 'ADMIN',
                    'status' => 'REGISTERED',
                    'registered_at' => now(),
                ]);
            }
        }
    }

    private function makeRegistrationsForPath(
        PpdbPeriod $period,
        AdmissionPath $path,
        int $count,
        string $prefix
    ): void {
        for ($i = 1; $i <= $count; $i++) {
            Registration::query()->create([
                'period_id' => $period->id,
                'admission_path_id' => $path->id,
                'major_id' => $this->major->id,

                'registration_number' =>
                    $prefix.'-'.str_pad(
                        (string) $i,
                        4,
                        '0',
                        STR_PAD_LEFT
                    ),

                'nik' =>
                    '3388888888'
                    .str_pad(
                        (string) (
                            (crc32($prefix) % 100000)
                        ),
                        5,
                        '0',
                        STR_PAD_LEFT
                    )
                    .str_pad(
                        (string) $i,
                        1,
                        '0',
                        STR_PAD_LEFT
                    ),

                'full_name' =>
                    'PATH COMPARISON '.$prefix.' '.$i,

                'birth_place' => 'KEBUMEN',
                'birth_date' => '2010-01-01',
                'gender' => 'L',
                'religion' => 'ISLAM',

                'whatsapp' =>
                    '0816'
                    .str_pad(
                        (string) (
                            ($i + (crc32($prefix) % 10000000))
                        ),
                        8,
                        '0',
                        STR_PAD_LEFT
                    ),

                'data_source' => 'ADMIN',
                'status' => 'REGISTERED',
                'registered_at' => now(),
            ]);
        }
    }

    private function makeRegistrationsForDataSource(
        PpdbPeriod $period,
        array $sourceCounts,
        string $prefix
    ): void {
        $sequence = 0;

        $path = $period->is($this->periodA)
            ? $this->pathA
            : $this->pathB;

        foreach ($sourceCounts as $source => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $sequence++;

                Registration::query()->create([
                    'period_id' => $period->id,
                    'admission_path_id' => $path->id,
                    'major_id' => $this->major->id,

                    'registration_number' =>
                        $prefix.'-'.str_pad(
                            (string) $sequence,
                            4,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'nik' =>
                        ($prefix === 'DA' || $prefix === 'DSA'
                            ? '3391111111'
                            : '3392222222')
                        .str_pad(
                            (string) $sequence,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'full_name' =>
                        'DATA SOURCE '.$prefix.' '.$sequence,

                    'birth_place' => 'KEBUMEN',
                    'birth_date' => '2010-01-01',
                    'gender' => 'L',
                    'religion' => 'ISLAM',

                    'whatsapp' =>
                        '0817'
                        .str_pad(
                            (string) (
                                ($prefix === 'DA' || $prefix === 'DSA'
                                    ? 10000000
                                    : 20000000)
                                + $sequence
                            ),
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

    private function makeRegistrationsForOriginSchool(
        PpdbPeriod $period,
        array $schoolCounts,
        string $prefix
    ): void {
        $sequence = 0;

        $path = $period->is($this->periodA)
            ? $this->pathA
            : $this->pathB;

        foreach ($schoolCounts as $originSchool => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $sequence++;

                Registration::query()->create([
                    'period_id' => $period->id,
                    'admission_path_id' => $path->id,
                    'major_id' => $this->major->id,

                    'registration_number' =>
                        $prefix.'-'.str_pad(
                            (string) $sequence,
                            4,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'nik' =>
                        (
                            str_contains($prefix, 'A')
                                ? '3393333333'
                                : '3394444444'
                        )
                        .str_pad(
                            (string) $sequence,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'full_name' =>
                        'ORIGIN SCHOOL '.$prefix.' '.$sequence,

                    'birth_place' => 'KEBUMEN',
                    'birth_date' => '2010-01-01',
                    'gender' => 'L',
                    'religion' => 'ISLAM',

                    'origin_school' => $originSchool,

                    'whatsapp' =>
                        '0818'
                        .str_pad(
                            (string) (
                                (
                                    str_contains($prefix, 'A')
                                        ? 10000000
                                        : 20000000
                                )
                                + $sequence
                            ),
                            8,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'data_source' => 'ADMIN',
                    'status' => 'REGISTERED',
                    'registered_at' => now(),
                ]);
            }
        }
    }

    private function makeRegistrationsForReferral(
        PpdbPeriod $period,
        array $referralCounts,
        string $prefix
    ): void {
        $sequence = 0;

        $path = $period->is($this->periodA)
            ? $this->pathA
            : $this->pathB;

        foreach ($referralCounts as $referrerName => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $sequence++;

                Registration::query()->create([
                    'period_id' => $period->id,
                    'admission_path_id' => $path->id,
                    'major_id' => $this->major->id,

                    'registration_number' =>
                        $prefix.'-'.str_pad(
                            (string) $sequence,
                            4,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'nik' =>
                        (
                            str_contains($prefix, 'A')
                                ? '3395555555'
                                : '3396666666'
                        )
                        .str_pad(
                            (string) $sequence,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'full_name' =>
                        'REFERRAL '.$prefix.' '.$sequence,

                    'birth_place' => 'KEBUMEN',
                    'birth_date' => '2010-01-01',
                    'gender' => 'L',
                    'religion' => 'ISLAM',

                    'referrer_name' => $referrerName,

                    'whatsapp' =>
                        '0819'
                        .str_pad(
                            (string) (
                                (
                                    str_contains($prefix, 'A')
                                        ? 10000000
                                        : 20000000
                                )
                                + $sequence
                            ),
                            8,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'data_source' => 'ADMIN',
                    'status' => 'REGISTERED',
                    'registered_at' => now(),
                ]);
            }
        }
    }

    private function makeRegistrationAt(
        PpdbPeriod $period,
        string $registeredAt,
        string $prefix
    ): void {
        static $sequence = 0;

        $sequence++;

        $path = $period->is($this->periodA)
            ? $this->pathA
            : $this->pathB;

        Registration::query()->create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $this->major->id,

            'registration_number' =>
                $prefix.'-'.str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'nik' =>
                '3397777777'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                'TREND '.$prefix.' '.$sequence,

            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',

            'whatsapp' =>
                '0820'
                .str_pad(
                    (string) (
                        10000000 + $sequence
                    ),
                    8,
                    '0',
                    STR_PAD_LEFT
                ),

            'data_source' => 'ADMIN',
            'status' => 'REGISTERED',
            'registered_at' => $registeredAt,
        ]);
    }

    private function makeReenrollmentFinanceData(
        PpdbPeriod $period,
        array $payments,
        string $prefix
    ): void {
        static $sequence = 0;

        $path = $period->is($this->periodA)
            ? $this->pathA
            : $this->pathB;

        foreach ($payments as $amount) {
            $sequence++;

            $registration = Registration::query()->create([
                'period_id' => $period->id,
                'admission_path_id' => $path->id,
                'major_id' => $this->major->id,

                'registration_number' =>
                    $prefix.'-'.str_pad(
                        (string) $sequence,
                        4,
                        '0',
                        STR_PAD_LEFT
                    ),

                'nik' =>
                    '3398888888'
                    .str_pad(
                        (string) $sequence,
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),

                'full_name' =>
                    'FINANCE '.$prefix.' '.$sequence,

                'birth_place' => 'KEBUMEN',
                'birth_date' => '2010-01-01',
                'gender' => 'L',
                'religion' => 'ISLAM',

                'whatsapp' =>
                    '0821'
                    .str_pad(
                        (string) (
                            10000000 + $sequence
                        ),
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

    private function makeFinanceRegistration(
        PpdbPeriod $period,
        AdmissionPath $path,
        string $prefix,
        string $status
    ): Registration {
        static $sequence = 10000;

        $sequence++;

        return Registration::query()->create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $this->major->id,

            'registration_number' =>
                $prefix.'-'.$sequence,

            'nik' =>
                '337700'
                .str_pad(
                    (string) $sequence,
                    10,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                'FINANCE ANOMALY '.$prefix,

            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',

            'whatsapp' =>
                '0822'
                .str_pad(
                    (string) $sequence,
                    8,
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

            'rejected_at' =>
                $status === 'REJECTED'
                    ? now()
                    : null,

            'reenrolled_at' =>
                $status === 'REENROLLED'
                    ? now()
                    : null,
        ]);
    }
}
