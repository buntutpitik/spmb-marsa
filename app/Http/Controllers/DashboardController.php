<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activePeriod = PpdbPeriod::query()
            ->where('is_active', true)
            ->where('status', 'OPEN')
            ->whereNull('archived_at')
            ->orderByDesc('year_start')
            ->first();

        $stats = [
            'total' => 0,
            'registered' => 0,
            'accepted' => 0,
            'rejected' => 0,
            'reenrolled' => 0,
            'withdrawn' => 0,
        ];

        $latestRegistrations = collect();
        $latestActivities = collect();

        if ($activePeriod) {
            $baseQuery = Registration::query()
                ->where('period_id', $activePeriod->id);

            $stats['total'] = (clone $baseQuery)->count();

            $stats['registered'] = (clone $baseQuery)
                ->where('status', 'REGISTERED')
                ->count();

            $stats['accepted'] = (clone $baseQuery)
                ->where('status', 'ACCEPTED')
                ->count();

            $stats['rejected'] = (clone $baseQuery)
                ->where('status', 'REJECTED')
                ->count();

            $stats['reenrolled'] = (clone $baseQuery)
                ->where('status', 'REENROLLED')
                ->count();

            $stats['withdrawn'] = (clone $baseQuery)
                ->where('status', 'WITHDRAWN')
                ->count();

            $latestRegistrations = Registration::query()
                ->with([
                    'major',
                    'admissionPath',
                ])
                ->where('period_id', $activePeriod->id)
                ->latest('registered_at')
                ->latest('id')
                ->take(5)
                ->get();

            $latestActivities = ActivityLog::query()
                ->whereHas(
                    'registration',
                    fn ($query) => $query->where(
                        'period_id',
                        $activePeriod->id
                    )
                )
                ->latest('id')
                ->take(5)
                ->get();
        }

        return view('dashboard', [
            'activePeriod' => $activePeriod,
            'stats' => $stats,
            'latestRegistrations' => $latestRegistrations,
            'latestActivities' => $latestActivities,
        ]);
    }
}