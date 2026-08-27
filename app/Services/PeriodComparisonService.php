<?php

namespace App\Services;

use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\ReenrollmentPayment;

class PeriodComparisonService
{
    private const STATUS_LABELS = [
        'REGISTERED' => 'Terdaftar',
        'ACCEPTED' => 'Diterima',
        'REJECTED' => 'Ditolak',
        'REENROLLED' => 'Daftar Ulang',
        'WITHDRAWN' => 'Mengundurkan Diri',
    ];

    public function compare(
        PpdbPeriod $periodA,
        PpdbPeriod $periodB
    ): array {
        $totalA = $this->totalRegistrations($periodA);
        $totalB = $this->totalRegistrations($periodB);

        $delta = $totalB - $totalA;

        return [
            'period_a' => $periodA,
            'period_b' => $periodB,

            'total_a' => $totalA,
            'total_b' => $totalB,
            'delta' => $delta,

            'growth' => $this->growth(
                $totalA,
                $totalB
            ),

            'status' => $this->compareStatuses(
                $periodA,
                $periodB,
                $totalA,
                $totalB
            ),

            'major_breakdown' => $this->buildMajorBreakdown(
                $periodA,
                $periodB,
                $totalA,
                $totalB
            ),

            'gender_breakdown' => $this->genderBreakdown(
                $periodA,
                $periodB,
                $totalA,
                $totalB
            ),

            'admission_path_breakdown' =>
                $this->admissionPathBreakdown(
                    $periodA,
                    $periodB,
                    $totalA,
                    $totalB
                ),

            'data_source_breakdown' =>
                $dataSourceBreakdown =
                    $this->dataSourceBreakdown(
                        $periodA,
                        $periodB,
                        $totalA,
                        $totalB
                    ),

            'referral_breakdown' => $this->referralBreakdown(
                $periodA,
                $periodB,
                $totalA,
                $totalB
            ),

            'self_service_rate' => [
                'a' => $this->dataSourceRate(
                    $dataSourceBreakdown,
                    'PUBLIC',
                    'share_a'
                ),
                'b' => $this->dataSourceRate(
                    $dataSourceBreakdown,
                    'PUBLIC',
                    'share_b'
                ),
            ],

            'origin_school_breakdown' => $this->originSchoolBreakdown(
                $periodA,
                $periodB,
                $totalA,
                $totalB
            ),

            'monthly_registration_trend' => $this->monthlyRegistrationTrend(
                $periodA,
                $periodB
            ),

            'reenrollment_finance' => $this->reenrollmentFinance(
                $periodA,
                $periodB
            ),
        ];
    }

    private function dataSourceBreakdown(
        PpdbPeriod $periodA,
        PpdbPeriod $periodB,
        int $totalA,
        int $totalB
    ): array {
        $definitions = [
            'PUBLIC' => 'Pendaftaran Mandiri',
            'ADMIN' => 'Input Panitia',
        ];

        $countsA = Registration::query()
            ->where('period_id', $periodA->id)
            ->selectRaw('data_source, COUNT(*) as total')
            ->groupBy('data_source')
            ->pluck('total', 'data_source');

        $countsB = Registration::query()
            ->where('period_id', $periodB->id)
            ->selectRaw('data_source, COUNT(*) as total')
            ->groupBy('data_source')
            ->pluck('total', 'data_source');

        return collect($definitions)
            ->map(function (
                string $label,
                string $source
            ) use (
                $countsA,
                $countsB,
                $totalA,
                $totalB
            ) {
                $countA = (int) ($countsA[$source] ?? 0);
                $countB = (int) ($countsB[$source] ?? 0);

                $shareA = $totalA > 0
                    ? ($countA / $totalA) * 100
                    : 0.0;

                $shareB = $totalB > 0
                    ? ($countB / $totalB) * 100
                    : 0.0;

                return [
                    'key' => $source,
                    'label' => $label,
                    'count_a' => $countA,
                    'count_b' => $countB,
                    'delta' => $countB - $countA,
                    'share_a' => $shareA,
                    'share_b' => $shareB,
                    'share_delta' => $shareB - $shareA,
                ];
            })
            ->values()
            ->all();
    }

    private function dataSourceRate(
        array $breakdown,
        string $source,
        string $field
    ): float {
        foreach ($breakdown as $row) {
            if (($row['key'] ?? null) === $source) {
                return (float) ($row[$field] ?? 0);
            }
        }

        return 0.0;
    }

    private function admissionPathBreakdown(
        PpdbPeriod $periodA,
        PpdbPeriod $periodB,
        int $totalA,
        int $totalB
    ): array {
        $pathsA = Registration::query()
            ->where('registrations.period_id', $periodA->id)
            ->join(
                'admission_paths',
                'admission_paths.id',
                '=',
                'registrations.admission_path_id'
            )
            ->selectRaw(
                'admission_paths.name as name, COUNT(*) as total'
            )
            ->groupBy('admission_paths.name')
            ->pluck('total', 'name');

        $pathsB = Registration::query()
            ->where('registrations.period_id', $periodB->id)
            ->join(
                'admission_paths',
                'admission_paths.id',
                '=',
                'registrations.admission_path_id'
            )
            ->selectRaw(
                'admission_paths.name as name, COUNT(*) as total'
            )
            ->groupBy('admission_paths.name')
            ->pluck('total', 'name');

        $names = $pathsA
            ->keys()
            ->merge($pathsB->keys())
            ->unique()
            ->sort()
            ->values();

        return $names
            ->map(function (string $name) use (
                $pathsA,
                $pathsB,
                $totalA,
                $totalB
            ) {
                $countA = (int) ($pathsA[$name] ?? 0);
                $countB = (int) ($pathsB[$name] ?? 0);

                $shareA = $totalA > 0
                    ? ($countA / $totalA) * 100
                    : 0.0;

                $shareB = $totalB > 0
                    ? ($countB / $totalB) * 100
                    : 0.0;

                return [
                    'name' => $name,
                    'count_a' => $countA,
                    'count_b' => $countB,
                    'delta' => $countB - $countA,
                    'share_a' => $shareA,
                    'share_b' => $shareB,
                    'share_delta' => $shareB - $shareA,
                ];
            })
            ->all();
    }

    private function compareStatuses(
        PpdbPeriod $periodA,
        PpdbPeriod $periodB,
        int $totalA,
        int $totalB
    ): array {
        $countsA = Registration::query()
            ->where('period_id', $periodA->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $countsB = Registration::query()
            ->where('period_id', $periodB->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $result = [];

        foreach (self::STATUS_LABELS as $status => $label) {
            $countA = (int) ($countsA[$status] ?? 0);
            $countB = (int) ($countsB[$status] ?? 0);

            $shareA = $totalA > 0
                ? ($countA / $totalA) * 100
                : 0.0;

            $shareB = $totalB > 0
                ? ($countB / $totalB) * 100
                : 0.0;

            $result[$status] = [
                'label' => $label,
                'a' => $countA,
                'b' => $countB,
                'delta' => $countB - $countA,
                'share_a' => $shareA,
                'share_b' => $shareB,
                'share_delta_pp' => $shareB - $shareA,
            ];
        }

        return $result;
    }

    private function buildMajorBreakdown(
        PpdbPeriod $periodA,
        PpdbPeriod $periodB,
        int $totalA,
        int $totalB
    ): array {
        $countsA = Registration::query()
            ->where('period_id', $periodA->id)
            ->whereNotNull('major_id')
            ->selectRaw('major_id, COUNT(*) as total')
            ->groupBy('major_id')
            ->pluck('total', 'major_id');

        $countsB = Registration::query()
            ->where('period_id', $periodB->id)
            ->whereNotNull('major_id')
            ->selectRaw('major_id, COUNT(*) as total')
            ->groupBy('major_id')
            ->pluck('total', 'major_id');

        $majorIds = $countsA
            ->keys()
            ->merge($countsB->keys())
            ->unique()
            ->values();

        $majors = Major::query()
            ->whereIn('id', $majorIds)
            ->get()
            ->keyBy('id');

        return $majorIds
            ->map(function ($majorId) use (
                $countsA,
                $countsB,
                $majors,
                $totalA,
                $totalB
            ) {
                $major = $majors->get($majorId);

                if (! $major) {
                    return null;
                }

                $countA = (int) ($countsA[$majorId] ?? 0);
                $countB = (int) ($countsB[$majorId] ?? 0);

                $shareA = $totalA > 0
                    ? ($countA / $totalA) * 100
                    : 0.0;

                $shareB = $totalB > 0
                    ? ($countB / $totalB) * 100
                    : 0.0;

                return [
                    'major_id' => $major->id,
                    'code' => $major->code,
                    'name' => $major->name,
                    'a' => $countA,
                    'b' => $countB,
                    'delta' => $countB - $countA,
                    'share_a' => $shareA,
                    'share_b' => $shareB,
                    'share_delta_pp' => $shareB - $shareA,
                ];
            })
            ->filter()
            ->sortBy(function (array $row) {
                return [
                    $row['code'] ?? '',
                    $row['name'] ?? '',
                ];
            })
            ->values()
            ->all();
    }

    private function genderBreakdown(
        PpdbPeriod $periodA,
        PpdbPeriod $periodB,
        int $totalA,
        int $totalB
    ): array {
        $definitions = [
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
        ];

        $countsA = Registration::query()
            ->where('period_id', $periodA->id)
            ->selectRaw('gender, COUNT(*) as total')
            ->groupBy('gender')
            ->pluck('total', 'gender');

        $countsB = Registration::query()
            ->where('period_id', $periodB->id)
            ->selectRaw('gender, COUNT(*) as total')
            ->groupBy('gender')
            ->pluck('total', 'gender');

        return collect($definitions)
            ->map(function (
                string $label,
                string $gender
            ) use (
                $countsA,
                $countsB,
                $totalA,
                $totalB
            ) {
                $countA = (int) ($countsA[$gender] ?? 0);
                $countB = (int) ($countsB[$gender] ?? 0);

                $shareA = $totalA > 0
                    ? ($countA / $totalA) * 100
                    : 0.0;

                $shareB = $totalB > 0
                    ? ($countB / $totalB) * 100
                    : 0.0;

                return [
                    'key' => $gender,
                    'label' => $label,
                    'count_a' => $countA,
                    'count_b' => $countB,
                    'delta' => $countB - $countA,
                    'share_a' => $shareA,
                    'share_b' => $shareB,
                    'share_delta' => $shareB - $shareA,
                ];
            })
            ->values()
            ->all();
    }

    private function totalRegistrations(
        PpdbPeriod $period
    ): int {
        return Registration::query()
            ->where('period_id', $period->id)
            ->count();
    }

    private function growth(
        int $totalA,
        int $totalB
    ): ?float {
        if ($totalA === 0) {
            return null;
        }

        return (($totalB - $totalA) / $totalA) * 100;
    }

    private function originSchoolBreakdown(
        PpdbPeriod $periodA,
        PpdbPeriod $periodB,
        int $totalA,
        int $totalB
    ): array {
        $countsA = $this->originSchoolCounts($periodA);
        $countsB = $this->originSchoolCounts($periodB);

        $schools = collect(array_keys($countsA))
            ->merge(array_keys($countsB))
            ->unique()
            ->sort()
            ->values();

        return $schools
            ->map(function (string $school) use (
                $countsA,
                $countsB,
                $totalA,
                $totalB
            ) {
                $countA = (int) ($countsA[$school] ?? 0);
                $countB = (int) ($countsB[$school] ?? 0);

                $shareA = $totalA > 0
                    ? ($countA / $totalA) * 100
                    : 0.0;

                $shareB = $totalB > 0
                    ? ($countB / $totalB) * 100
                    : 0.0;

                return [
                    'name' => $school,
                    'count_a' => $countA,
                    'count_b' => $countB,
                    'delta' => $countB - $countA,
                    'share_a' => $shareA,
                    'share_b' => $shareB,
                    'share_delta' => $shareB - $shareA,
                ];
            })
            ->all();
    }

    private function originSchoolCounts(
        PpdbPeriod $period
    ): array {
        return Registration::query()
            ->where('period_id', $period->id)
            ->whereNotNull('origin_school')
            ->pluck('origin_school')
            ->map(function ($school) {
                $school = trim((string) $school);

                $school = preg_replace(
                    '/\s+/u',
                    ' ',
                    $school
                );

                return mb_strtoupper(
                    $school,
                    'UTF-8'
                );
            })
            ->filter()
            ->countBy()
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    private function referralBreakdown(
        PpdbPeriod $periodA,
        PpdbPeriod $periodB,
        int $totalA,
        int $totalB
    ): array {
        $countsA = $this->referralCounts($periodA);
        $countsB = $this->referralCounts($periodB);

        $referrals = collect(array_keys($countsA))
            ->merge(array_keys($countsB))
            ->unique()
            ->sort()
            ->values();

        return $referrals
            ->map(function (string $name) use (
                $countsA,
                $countsB,
                $totalA,
                $totalB
            ) {
                $countA = (int) ($countsA[$name] ?? 0);
                $countB = (int) ($countsB[$name] ?? 0);

                $shareA = $totalA > 0
                    ? ($countA / $totalA) * 100
                    : 0.0;

                $shareB = $totalB > 0
                    ? ($countB / $totalB) * 100
                    : 0.0;

                return [
                    'name' => $name,
                    'count_a' => $countA,
                    'count_b' => $countB,
                    'delta' => $countB - $countA,
                    'share_a' => $shareA,
                    'share_b' => $shareB,
                    'share_delta' => $shareB - $shareA,
                ];
            })
            ->all();
    }

    private function referralCounts(
        PpdbPeriod $period
    ): array {
        return Registration::query()
            ->where('period_id', $period->id)
            ->pluck('referrer_name')
            ->map(function ($name) {
                $name = trim((string) $name);

                $name = preg_replace(
                    '/\s+/u',
                    ' ',
                    $name
                );

                return mb_strtoupper(
                    $name,
                    'UTF-8'
                );
            })
            ->filter(fn (string $name) => $name !== '')
            ->countBy()
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    private function monthlyRegistrationTrend(
        PpdbPeriod $periodA,
        PpdbPeriod $periodB
    ): array {
        $monthLabels = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $countsA = Registration::query()
            ->where('period_id', $periodA->id)
            ->whereNotNull('registered_at')
            ->selectRaw(
                'MONTH(registered_at) as month_number, COUNT(*) as total'
            )
            ->groupByRaw('MONTH(registered_at)')
            ->pluck('total', 'month_number');

        $countsB = Registration::query()
            ->where('period_id', $periodB->id)
            ->whereNotNull('registered_at')
            ->selectRaw(
                'MONTH(registered_at) as month_number, COUNT(*) as total'
            )
            ->groupByRaw('MONTH(registered_at)')
            ->pluck('total', 'month_number');

        return collect($monthLabels)
            ->map(function (
                string $label,
                int $month
            ) use (
                $countsA,
                $countsB
            ) {
                $countA = (int) ($countsA[$month] ?? 0);
                $countB = (int) ($countsB[$month] ?? 0);

                return [
                    'month' => $month,
                    'label' => $label,
                    'count_a' => $countA,
                    'count_b' => $countB,
                    'delta' => $countB - $countA,
                ];
            })
            ->values()
            ->all();
    }

    private function reenrollmentFinance(
        PpdbPeriod $periodA,
        PpdbPeriod $periodB
    ): array {
        $summaryA = $this->financeSummaryForPeriod($periodA);
        $summaryB = $this->financeSummaryForPeriod($periodB);

        return [
            'reenrolled_a' => $summaryA['reenrolled'],
            'reenrolled_b' => $summaryB['reenrolled'],
            'reenrolled_delta' =>
                $summaryB['reenrolled'] - $summaryA['reenrolled'],

            'transactions_a' => $summaryA['transactions'],
            'transactions_b' => $summaryB['transactions'],
            'transactions_delta' =>
                $summaryB['transactions'] - $summaryA['transactions'],

            'payment_a' => $summaryA['payment'],
            'payment_b' => $summaryB['payment'],
            'payment_delta' =>
                $summaryB['payment'] - $summaryA['payment'],
        ];
    }

    private function financeSummaryForPeriod(
        PpdbPeriod $period
    ): array {
        $reenrolled = Registration::query()
            ->where('period_id', $period->id)
            ->where('status', 'REENROLLED')
            ->count();

        $paymentQuery = ReenrollmentPayment::query()
            ->whereHas(
                'registration',
                fn ($query) =>
                    $query->where('period_id', $period->id)
            );

        return [
            'reenrolled' => $reenrolled,

            'transactions' => (clone $paymentQuery)
                ->count(),

            'payment' => (int) (clone $paymentQuery)
                ->sum('amount'),
        ];
    }
}
