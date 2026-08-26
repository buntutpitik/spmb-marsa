<?php

namespace App\Services;

use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Http\Request;

class PeriodContext
{
    public function resolveAdminPeriod(
        Request $request
    ): ?PpdbPeriod {
        if ($request->filled('period_id')) {
            return PpdbPeriod::query()
                ->whereNull('archived_at')
                ->findOrFail(
                    $request->integer('period_id')
                );
        }

        return PpdbPeriod::query()
            ->whereNull('archived_at')
            ->where('is_active', true)
            ->orderByDesc('year_start')
            ->first()
            ?? PpdbPeriod::query()
                ->whereNull('archived_at')
                ->orderByDesc('year_start')
                ->first();
    }

    public function resolveRegistrationPeriod(
        Request $request,
        Registration $registration
    ): PpdbPeriod {
        $requestedPeriodId = $request->query('period_id');

        if ($requestedPeriodId === null || $requestedPeriodId === '') {
            return PpdbPeriod::query()
                ->whereNull('archived_at')
                ->findOrFail($registration->period_id);
        }

        $period = PpdbPeriod::query()
            ->whereNull('archived_at')
            ->findOrFail($requestedPeriodId);

        abort_unless(
            (int) $registration->period_id === (int) $period->id,
            404
        );

        return $period;
    }
}