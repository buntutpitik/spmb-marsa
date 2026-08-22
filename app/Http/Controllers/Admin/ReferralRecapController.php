<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReferralRecapController extends Controller
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

        $referralRecaps = collect();

        if ($selectedPeriod) {
            $query = Registration::query()
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
                });

            if ($request->filled('q')) {
                $keyword = trim(
                    (string) $request->input('q')
                );

                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery
                        ->where(
                            'referrer_name',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhere(
                            'referrer_source',
                            'like',
                            "%{$keyword}%"
                        );
                });
            }

            $referralRecaps = $query
                ->selectRaw("
                    TRIM(
                        COALESCE(
                            referrer_name,
                            ''
                        )
                    ) as referrer_name_label
                ")
                ->selectRaw("
                    TRIM(
                        COALESCE(
                            referrer_source,
                            ''
                        )
                    ) as referrer_source_label
                ")
                ->selectRaw(
                    'COUNT(*) as total'
                )
                ->selectRaw(
                    "SUM(
                        CASE
                            WHEN status = 'REGISTERED'
                            THEN 1
                            ELSE 0
                        END
                    ) as registered"
                )
                ->selectRaw(
                    "SUM(
                        CASE
                            WHEN status = 'ACCEPTED'
                            THEN 1
                            ELSE 0
                        END
                    ) as accepted"
                )
                ->selectRaw(
                    "SUM(
                        CASE
                            WHEN status = 'REJECTED'
                            THEN 1
                            ELSE 0
                        END
                    ) as rejected"
                )
                ->selectRaw(
                    "SUM(
                        CASE
                            WHEN status = 'REENROLLED'
                            THEN 1
                            ELSE 0
                        END
                    ) as reenrolled"
                )
                ->selectRaw(
                    "SUM(
                        CASE
                            WHEN status = 'WITHDRAWN'
                            THEN 1
                            ELSE 0
                        END
                    ) as withdrawn"
                )
                ->groupBy(
                    'referrer_name',
                    'referrer_source'
                )
                ->orderByDesc('total')
                ->orderBy('referrer_name')
                ->orderBy('referrer_source')
                ->get()
                ->map(function ($row) {
                    $row->referrer_name_label =
                        trim(
                            (string) $row->referrer_name_label
                        ) !== ''
                            ? $row->referrer_name_label
                            : '-';

                    $row->referrer_source_label =
                        trim(
                            (string) $row->referrer_source_label
                        ) !== ''
                            ? $row->referrer_source_label
                            : '-';

                    return $row;
                });
        }

        return view(
            'admin.recaps.referrals',
            [
                'periods' => $periods,
                'selectedPeriod' => $selectedPeriod,
                'referralRecaps' => $referralRecaps,
            ]
        );
    }
}