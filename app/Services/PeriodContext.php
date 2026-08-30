<?php

namespace App\Services;

use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Http\Request;

class PeriodContext
{
    public function resolveExplicitPeriod(
        Request $request
    ): PpdbPeriod {
        abort_unless(
            $request->filled('period_id'),
            404
        );

        return PpdbPeriod::query()
            ->whereNull('archived_at')
            ->findOrFail(
                $request->integer('period_id')
            );
    }

    public function resolveWritableExplicitPeriod(
        Request $request
    ): PpdbPeriod {
        $period = $this->resolveExplicitPeriod($request);

        abort_if(
            $period->isReadOnly(),
            409,
            'Periode yang sudah ditutup bersifat read-only.'
        );

        return $period;
    }

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

    public function resolveActivePeriod(
        ?int $periodId = null
    ): ?PpdbPeriod {
        $query = PpdbPeriod::query()
            ->whereNull('archived_at')
            ->where('is_active', true)
            ->where('status', 'OPEN');

        if ($periodId !== null) {
            $query->whereKey($periodId);
        }

        return $query
            ->orderByDesc('year_start')
            ->first();
    }

    public function resolveRegistrationPeriod(
        Request $request,
        Registration $registration
    ): PpdbPeriod {
        $requestedPeriodId = $request->query('period_id');

        if (
            $requestedPeriodId === null
            || $requestedPeriodId === ''
        ) {
            return PpdbPeriod::query()
                ->whereNull('archived_at')
                ->findOrFail(
                    $registration->period_id
                );
        }

        $period = PpdbPeriod::query()
            ->whereNull('archived_at')
            ->findOrFail($requestedPeriodId);

        abort_unless(
            (int) $registration->period_id
                === (int) $period->id,
            404
        );

        return $period;
    }
}