<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $periods = PpdbPeriod::query()
            ->whereNull('archived_at')
            ->orderByDesc('year_start')
            ->get();

        $selectedPeriod = null;

        if ($request->filled('period_id')) {
            $selectedPeriod = $periods->firstWhere(
                'id',
                $request->integer('period_id')
            );
        }

        if (! $selectedPeriod) {
            $selectedPeriod = $periods
                ->firstWhere('is_active', true)
                ?? $periods->first();
        }

        $summary = [
            'TOTAL' => 0,
            'REGISTERED' => 0,
            'ACCEPTED' => 0,
            'REJECTED' => 0,
            'REENROLLED' => 0,
            'WITHDRAWN' => 0,
        ];

        $dailyTrend = collect();
        $statusDistribution = collect();
        $majorDistribution = collect();
        $topOriginSchools = collect();
        $topReferrals = collect();

        if ($selectedPeriod) {
            $baseQuery = Registration::query()
                ->where(
                    'period_id',
                    $selectedPeriod->id
                );

            $summary['TOTAL'] = (clone $baseQuery)
                ->count();

            foreach ([
                'REGISTERED',
                'ACCEPTED',
                'REJECTED',
                'REENROLLED',
                'WITHDRAWN',
            ] as $status) {
                $summary[$status] = (clone $baseQuery)
                    ->where('status', $status)
                    ->count();
            }

            $dailyTrend = Registration::query()
                ->where(
                    'period_id',
                    $selectedPeriod->id
                )
                ->whereNotNull('registered_at')
                ->selectRaw(
                    'DATE(registered_at) as registration_date'
                )
                ->selectRaw(
                    'COUNT(*) as total'
                )
                ->groupByRaw(
                    'DATE(registered_at)'
                )
                ->orderBy('registration_date')
                ->get();

            $statusDistribution = collect([
                [
                    'key' => 'REGISTERED',
                    'label' => 'Terdaftar',
                    'total' => $summary['REGISTERED'],
                ],
                [
                    'key' => 'ACCEPTED',
                    'label' => 'Diterima',
                    'total' => $summary['ACCEPTED'],
                ],
                [
                    'key' => 'REJECTED',
                    'label' => 'Ditolak',
                    'total' => $summary['REJECTED'],
                ],
                [
                    'key' => 'REENROLLED',
                    'label' => 'Daftar Ulang',
                    'total' => $summary['REENROLLED'],
                ],
                [
                    'key' => 'WITHDRAWN',
                    'label' => 'Mengundurkan Diri',
                    'total' => $summary['WITHDRAWN'],
                ],
            ]);

            $majorDistribution = DB::table('registrations')
                ->join(
                    'majors',
                    'majors.id',
                    '=',
                    'registrations.major_id'
                )
                ->where(
                    'registrations.period_id',
                    $selectedPeriod->id
                )
                ->select([
                    'majors.id',
                    'majors.code',
                    'majors.name',
                ])
                ->selectRaw(
                    'COUNT(registrations.id) as total'
                )
                ->groupBy(
                    'majors.id',
                    'majors.code',
                    'majors.name'
                )
                ->orderByDesc('total')
                ->orderBy('majors.code')
                ->get();

            $topOriginSchools = Registration::query()
                ->where(
                    'period_id',
                    $selectedPeriod->id
                )
                ->whereNotNull('origin_school')
                ->where('origin_school', '<>', '')
                ->select('origin_school')
                ->selectRaw(
                    'COUNT(*) as total'
                )
                ->groupBy('origin_school')
                ->orderByDesc('total')
                ->orderBy('origin_school')
                ->limit(10)
                ->get();

            $topReferrals = Registration::query()
                ->where(
                    'period_id',
                    $selectedPeriod->id
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
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(function ($row) {
                    $row->referrer_name_label =
                        filled(trim((string) $row->referrer_name))
                            ? trim((string) $row->referrer_name)
                            : '-';

                    $row->referrer_source_label =
                        filled(trim((string) $row->referrer_source))
                            ? trim((string) $row->referrer_source)
                            : '-';

                    return $row;
                });
        }

        return view(
            'admin.analytics.index',
            [
                'periods' => $periods,
                'selectedPeriod' => $selectedPeriod,
                'summary' => $summary,
                'dailyTrend' => $dailyTrend,
                'statusDistribution' => $statusDistribution,
                'majorDistribution' => $majorDistribution,
                'topOriginSchools' => $topOriginSchools,
                'topReferrals' => $topReferrals,
            ]
        );
    }
}