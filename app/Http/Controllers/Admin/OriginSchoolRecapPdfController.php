<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OriginSchoolRecapPdfController extends Controller
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

        $rows = Registration::query()
            ->where(
                'period_id',
                $period->id
            )
            ->whereNotNull('origin_school')
            ->whereRaw("TRIM(origin_school) <> ''")
            ->select('origin_school')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                "SUM(CASE WHEN status = 'REGISTERED' THEN 1 ELSE 0 END) as registered"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'ACCEPTED' THEN 1 ELSE 0 END) as accepted"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'REJECTED' THEN 1 ELSE 0 END) as rejected"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'REENROLLED' THEN 1 ELSE 0 END) as reenrolled"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'WITHDRAWN' THEN 1 ELSE 0 END) as withdrawn"
            )
            ->groupBy('origin_school')
            ->orderByDesc('total')
            ->orderBy('origin_school')
            ->get();

        $totals = [
            'total' => (int) $rows->sum('total'),
            'registered' => (int) $rows->sum('registered'),
            'accepted' => (int) $rows->sum('accepted'),
            'rejected' => (int) $rows->sum('rejected'),
            'reenrolled' => (int) $rows->sum('reenrolled'),
            'withdrawn' => (int) $rows->sum('withdrawn'),
        ];

        $filename =
            'rekap-asal-sekolah-'
            .str_replace(
                '/',
                '-',
                $period->name
            )
            .'.pdf';

        return Pdf::loadView(
            'admin.reports.pdf.origin-school-recap',
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