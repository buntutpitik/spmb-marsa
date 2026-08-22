<?php

namespace App\Services;

use App\Models\AdmissionPath;
use App\Models\PpdbPeriod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdmissionPathResolver
{
    public function resolve(
        PpdbPeriod $period,
        ?CarbonInterface $date = null
    ): AdmissionPath {
        $date ??= now();

        $path = AdmissionPath::query()
            ->where('period_id', $period->id)
            ->where('is_active', true)
            ->where(function ($query) use ($date) {
                $query
                    ->whereNull('start_date')
                    ->orWhereDate(
                        'start_date',
                        '<=',
                        $date->toDateString()
                    );
            })
            ->where(function ($query) use ($date) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate(
                        'end_date',
                        '>=',
                        $date->toDateString()
                    );
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (! $path) {
            throw (new ModelNotFoundException())
                ->setModel(AdmissionPath::class);
        }

        return $path;
    }
}