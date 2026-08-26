<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PeriodContext;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReferralRecapPdfController extends Controller
{
    public function __construct(
        protected PeriodContext $periodContext
    ) {
    }
    public function download(
        Request $request
    ): Response {
        $period = $this->periodContext
            ->resolveExplicitPeriod($request);

        $period->load('school');

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
            ->select([
                'referrer_name',
                'referrer_source',
            ])
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
                        (string) $row->referrer_name
                    ) !== ''
                        ? trim(
                            (string) $row->referrer_name
                        )
                        : '-';

                $row->referrer_source_label =
                    trim(
                        (string) $row->referrer_source
                    ) !== ''
                        ? trim(
                            (string) $row->referrer_source
                        )
                        : '-';

                return $row;
            });

        $totals = [
            'total' => (int) $rows->sum('total'),
            'registered' => (int) $rows->sum('registered'),
            'accepted' => (int) $rows->sum('accepted'),
            'rejected' => (int) $rows->sum('rejected'),
            'reenrolled' => (int) $rows->sum('reenrolled'),
            'withdrawn' => (int) $rows->sum('withdrawn'),
        ];

        $filename =
            'rekap-referral-pembawa-'
            .str_replace(
                '/',
                '-',
                $period->name
            )
            .'.pdf';

        return Pdf::loadView(
            'admin.reports.pdf.referral-recap',
            [
                'period' => $period,
                'rows' => $rows,
                'totals' => $totals,
            ]
        )
            ->setPaper(
                'a4',
                'landscape'
            )
            ->download(
                $filename
            );
    }
}