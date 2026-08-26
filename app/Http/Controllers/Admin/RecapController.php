<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Services\PeriodContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecapController extends Controller
{
    public function __construct(
        protected PeriodContext $periodContext
    ) {
    }

    public function index(Request $request): View
    {
        $periods = PpdbPeriod::query()
            ->whereNull('archived_at')
            ->orderByDesc('year_start')
            ->get();

        $selectedPeriod = $this->periodContext
            ->resolveAdminPeriod($request);

        $summary = [
            'TOTAL' => 0,
            'REGISTERED' => 0,
            'ACCEPTED' => 0,
            'REJECTED' => 0,
            'REENROLLED' => 0,
            'WITHDRAWN' => 0,
        ];

        $majorRecaps = collect();

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

            $majorRecaps = DB::table('period_majors')
                ->join(
                    'majors',
                    'majors.id',
                    '=',
                    'period_majors.major_id'
                )
                ->where(
                    'period_majors.period_id',
                    $selectedPeriod->id
                )
                ->where(
                    'period_majors.is_active',
                    true
                )
                ->select([
                    'majors.id',
                    'majors.code',
                    'majors.name',
                    'majors.sort_order',
                ])
                ->selectRaw(
                    'COUNT(registrations.id) as total'
                )
                ->selectRaw(
                    "SUM(CASE WHEN registrations.gender = 'L' THEN 1 ELSE 0 END) as male"
                )
                ->selectRaw(
                    "SUM(CASE WHEN registrations.gender = 'P' THEN 1 ELSE 0 END) as female"
                )
                ->selectRaw(
                    "SUM(CASE WHEN registrations.status = 'REGISTERED' THEN 1 ELSE 0 END) as registered"
                )
                ->selectRaw(
                    "SUM(CASE WHEN registrations.status = 'ACCEPTED' THEN 1 ELSE 0 END) as accepted"
                )
                ->selectRaw(
                    "SUM(CASE WHEN registrations.status = 'REJECTED' THEN 1 ELSE 0 END) as rejected"
                )
                ->selectRaw(
                    "SUM(CASE WHEN registrations.status = 'REENROLLED' THEN 1 ELSE 0 END) as reenrolled"
                )
                ->selectRaw(
                    "SUM(CASE WHEN registrations.status = 'WITHDRAWN' THEN 1 ELSE 0 END) as withdrawn"
                )
                ->leftJoin('registrations', function ($join) use ($selectedPeriod) {
                    $join
                        ->on(
                            'registrations.major_id',
                            '=',
                            'majors.id'
                        )
                        ->where(
                            'registrations.period_id',
                            '=',
                            $selectedPeriod->id
                        );
                })
                ->groupBy(
                    'majors.id',
                    'majors.code',
                    'majors.name',
                    'majors.sort_order'
                )
                ->orderBy('majors.sort_order')
                ->orderBy('majors.name')
                ->get();
        }

        return view('admin.recaps.index', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'summary' => $summary,
            'majorRecaps' => $majorRecaps,
        ]);
    }
}