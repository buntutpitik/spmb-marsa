<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OriginSchoolRecapController extends Controller
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

        $originSchoolRecaps = collect();

        if ($selectedPeriod) {
            $query = Registration::query()
                ->where(
                    'period_id',
                    $selectedPeriod->id
                )
                ->whereNotNull('origin_school')
                ->where('origin_school', '<>', '')
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
                ->orderBy('origin_school');

            if ($request->filled('q')) {
                $keyword = trim(
                    (string) $request->input('q')
                );

                $query->where(
                    'origin_school',
                    'like',
                    "%{$keyword}%"
                );
            }

            $originSchoolRecaps = $query->get();
        }

        return view(
            'admin.recaps.origin-schools',
            [
                'periods' => $periods,
                'selectedPeriod' => $selectedPeriod,
                'originSchoolRecaps' => $originSchoolRecaps,
            ]
        );
    }
}