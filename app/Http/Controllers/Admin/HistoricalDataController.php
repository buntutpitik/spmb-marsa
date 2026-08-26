<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use Illuminate\Contracts\View\View;

class HistoricalDataController extends Controller
{
    public function index(): View
    {
        $periods = PpdbPeriod::query()
            ->whereNull('archived_at')
            ->where(function ($query) {
                $query
                    ->where('status', '<>', 'OPEN')
                    ->orWhere('is_active', false);
            })
            ->withCount('registrations')
            ->orderByDesc('year_start')
            ->get();

        return view('admin.historical.index', [
            'periods' => $periods,
        ]);
    }
}