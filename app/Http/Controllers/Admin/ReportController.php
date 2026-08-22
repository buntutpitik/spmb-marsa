<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
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
            'TOTAL_PAYMENT' => 0,
        ];

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

            $summary['TOTAL_PAYMENT'] = (int) DB::table(
                'reenrollment_payments'
            )
                ->join(
                    'registrations',
                    'registrations.id',
                    '=',
                    'reenrollment_payments.registration_id'
                )
                ->where(
                    'registrations.period_id',
                    $selectedPeriod->id
                )
                ->sum(
                    'reenrollment_payments.amount'
                );
        }

        return view(
            'admin.reports.index',
            [
                'periods' => $periods,
                'selectedPeriod' => $selectedPeriod,
                'summary' => $summary,
            ]
        );
    }
}