<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReenrollmentFinanceRecapController extends Controller
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
            'TOTAL_STUDENTS' => 0,
            'TOTAL_BILLED' => 0,
            'TOTAL_PAID' => 0,
            'TOTAL_REMAINING' => 0,
            'WAITING' => 0,
            'PAID_OFF' => 0,
        ];

        $registrations = Registration::query()
            ->whereRaw('1 = 0')
            ->paginate(10);

        if ($selectedPeriod) {
            $query = Registration::query()
                ->with([
                    'period',
                    'major',
                    'reenrollmentPayments',
                ])
                ->where(
                    'period_id',
                    $selectedPeriod->id
                )
                ->whereIn('status', [
                    'ACCEPTED',
                    'REENROLLED',
                ]);

            if ($request->filled('q')) {
                $keyword = trim(
                    (string) $request->input('q')
                );

                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery
                        ->where(
                            'registration_number',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhere(
                            'nik',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhere(
                            'nisn',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhere(
                            'full_name',
                            'like',
                            "%{$keyword}%"
                        );
                });
            }

            if ($request->filled('payment_status')) {
                if (
                    $request->input('payment_status')
                    === 'WAITING'
                ) {
                    $query->where(
                        'status',
                        'ACCEPTED'
                    );
                }

                if (
                    $request->input('payment_status')
                    === 'PAID_OFF'
                ) {
                    $query->where(
                        'status',
                        'REENROLLED'
                    );
                }
            }

            $allRegistrations = Registration::query()
                ->with([
                    'period',
                    'reenrollmentPayments',
                ])
                ->where(
                    'period_id',
                    $selectedPeriod->id
                )
                ->whereIn('status', [
                    'ACCEPTED',
                    'REENROLLED',
                ])
                ->get();

            foreach ($allRegistrations as $registration) {
                $requiredFee = (int) (
                    $registration->period?->default_reenroll_fee
                    ?? 0
                );

                $totalPaid = (int) $registration
                    ->reenrollmentPayments
                    ->sum('amount');

                $remaining = max(
                    0,
                    $requiredFee - $totalPaid
                );

                $summary['TOTAL_STUDENTS']++;

                $summary['TOTAL_BILLED'] +=
                    $requiredFee;

                $summary['TOTAL_PAID'] +=
                    $totalPaid;

                $summary['TOTAL_REMAINING'] +=
                    $remaining;

                if (
                    $requiredFee > 0
                    && $remaining === 0
                ) {
                    $summary['PAID_OFF']++;
                } else {
                    $summary['WAITING']++;
                }
            }

            $registrations = $query
                ->latest('registered_at')
                ->latest('id')
                ->paginate(10)
                ->withQueryString();
        }

        return view(
            'admin.recaps.reenrollment-finance',
            [
                'periods' => $periods,
                'selectedPeriod' => $selectedPeriod,
                'summary' => $summary,
                'registrations' => $registrations,
            ]
        );
    }
}