<?php

namespace App\Services;

use App\Models\PpdbPeriod;
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
}