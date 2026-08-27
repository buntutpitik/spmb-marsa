<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PeriodComparisonExport;
use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Services\PeriodComparisonService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

        $sections = [
            'summary',
            'status',
            'major',
            'gender',
            'admission-path',
            'data-source',
            'origin-school',
            'referral',
            'trend',
            'finance',
        ];

        $section = $request->string('section')->toString();

        if (! in_array($section, $sections, true)) {
            $section = 'summary';
        }

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
            'section' => $section,
        ]);
    }

    public function export(
        Request $request,
        PeriodComparisonService $comparisonService
    ) {
        $validated = $request->validate([
            'period_a' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],
            'period_b' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
                'different:period_a',
            ],
        ]);

        $periodA = PpdbPeriod::query()
            ->findOrFail($validated['period_a']);

        $periodB = PpdbPeriod::query()
            ->findOrFail($validated['period_b']);

        $comparison = $comparisonService->compare(
            $periodA,
            $periodB
        );

        $filename = sprintf(
            'perbandingan-spmb-%s-vs-%s.xlsx',
            str_replace('/', '-', $periodA->name),
            str_replace('/', '-', $periodB->name)
        );

        return Excel::download(
            new PeriodComparisonExport($comparison),
            $filename
        );
    }
}
