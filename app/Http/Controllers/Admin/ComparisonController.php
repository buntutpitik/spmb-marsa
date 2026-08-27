<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Services\PeriodComparisonService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ComparisonController extends Controller
{
    public function __construct(
        protected PeriodComparisonService $comparisonService
    ) {
    }

    public function index(Request $request): View
    {
        $periods = PpdbPeriod::query()
            ->whereNull('archived_at')
            ->orderBy('year_start')
            ->get();

        $periodA = null;
        $periodB = null;
        $comparison = null;

        if ($request->filled('period_a')) {
            $periodA = PpdbPeriod::query()
                ->whereNull('archived_at')
                ->findOrFail(
                    (int) $request->integer('period_a')
                );
        }

        if ($request->filled('period_b')) {
            $periodB = PpdbPeriod::query()
                ->whereNull('archived_at')
                ->findOrFail(
                    (int) $request->integer('period_b')
                );
        }

        if (
            $periodA
            && $periodB
            && $periodA->is($periodB)
        ) {
            abort(
                422,
                'Periode A dan Periode B harus berbeda.'
            );
        }


        if ($periodA && $periodB) {
            $comparison = $this->comparisonService
                ->compare(
                    $periodA,
                    $periodB
                );
        }

        return view('admin.comparison.index', [
            'periods' => $periods,
            'periodA' => $periodA,
            'periodB' => $periodB,
            'comparison' => $comparison,
        ]);
    }
}