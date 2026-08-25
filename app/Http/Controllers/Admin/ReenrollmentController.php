<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReenrollmentController extends Controller
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

        $majors = collect();

        $summary = [
            'WAITING' => 0,
            'PAID_OFF' => 0,
            'TOTAL_RECEIVED' => 0,
        ];

        $registrations = Registration::query()
            ->whereRaw('1 = 0')
            ->paginate(10);

        if ($selectedPeriod) {
            $majors = $selectedPeriod->majors()
                ->wherePivot('is_active', true)
                ->orderBy('majors.sort_order')
                ->orderBy('majors.name')
                ->get();

            $baseQuery = Registration::query()
                ->where('period_id', $selectedPeriod->id)
                ->whereIn('status', [
                    'ACCEPTED',
                    'REENROLLED',
                ]);

            $summary['WAITING'] = (clone $baseQuery)
                ->where('status', 'ACCEPTED')
                ->count();

            $summary['PAID_OFF'] = (clone $baseQuery)
                ->where('status', 'REENROLLED')
                ->count();

            $summary['TOTAL_RECEIVED'] = (int) DB::table(
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
                ->sum('reenrollment_payments.amount');

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
                        )
                        ->orWhere(
                            'origin_school',
                            'like',
                            "%{$keyword}%"
                        );
                });
            }

            if ($request->filled('major_id')) {
                $query->where(
                    'major_id',
                    $request->integer('major_id')
                );
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

            $registrations = $query
                ->latest('registered_at')
                ->latest('id')
                ->paginate(10)
                ->withQueryString();
        }

        return view('admin.reenrollments.index', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'majors' => $majors,
            'summary' => $summary,
            'registrations' => $registrations,
        ]);
    }
}