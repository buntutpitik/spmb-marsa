<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MajorRecapPdfController extends Controller
{
    public function download(
        Request $request
    ): Response {
        $period = PpdbPeriod::query()
            ->with('school')
            ->whereNull('archived_at')
            ->findOrFail(
                $request->integer('period_id')
            );

        $rows = DB::table('period_majors')
            ->join(
                'majors',
                'majors.id',
                '=',
                'period_majors.major_id'
            )
            ->leftJoin('registrations', function ($join) use ($period) {
                $join
                    ->on(
                        'registrations.major_id',
                        '=',
                        'majors.id'
                    )
                    ->where(
                        'registrations.period_id',
                        '=',
                        $period->id
                    );
            })
            ->where(
                'period_majors.period_id',
                $period->id
            )
            ->where(
                'period_majors.is_active',
                true
            )
            ->select([
                'majors.code',
                'majors.name',
                'majors.sort_order',
            ])
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN registrations.gender = 'L'
                        THEN 1
                        ELSE 0
                    END
                ) as male"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN registrations.gender = 'P'
                        THEN 1
                        ELSE 0
                    END
                ) as female"
            )
            ->selectRaw(
                'COUNT(registrations.id) as total'
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN registrations.status = 'REGISTERED'
                        THEN 1
                        ELSE 0
                    END
                ) as registered"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN registrations.status = 'ACCEPTED'
                        THEN 1
                        ELSE 0
                    END
                ) as accepted"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN registrations.status = 'REJECTED'
                        THEN 1
                        ELSE 0
                    END
                ) as rejected"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN registrations.status = 'REENROLLED'
                        THEN 1
                        ELSE 0
                    END
                ) as reenrolled"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN registrations.status = 'WITHDRAWN'
                        THEN 1
                        ELSE 0
                    END
                ) as withdrawn"
            )
            ->groupBy(
                'majors.code',
                'majors.name',
                'majors.sort_order'
            )
            ->orderBy('majors.sort_order')
            ->orderBy('majors.name')
            ->get();

        $totals = [
            'male' => (int) $rows->sum('male'),
            'female' => (int) $rows->sum('female'),
            'total' => (int) $rows->sum('total'),
            'registered' => (int) $rows->sum('registered'),
            'accepted' => (int) $rows->sum('accepted'),
            'rejected' => (int) $rows->sum('rejected'),
            'reenrolled' => (int) $rows->sum('reenrolled'),
            'withdrawn' => (int) $rows->sum('withdrawn'),
        ];

        $filename =
            'rekap-jurusan-'
            .str_replace(
                '/',
                '-',
                $period->name
            )
            .'.pdf';

        return Pdf::loadView(
            'admin.reports.pdf.major-recap',
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