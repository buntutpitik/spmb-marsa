<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\ReliefOption;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReliefOptionController extends Controller
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
                (int) $request->input('period_id')
            );
        }

        if (! $selectedPeriod) {
            $selectedPeriod = $periods
                ->firstWhere('is_active', true)
                ?? $periods->first();
        }

        $reliefOptions = ReliefOption::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $periodOptionIds = collect();

        if ($selectedPeriod) {
            $periodOptionIds = $selectedPeriod
                ->reliefOptions()
                ->wherePivot('is_active', true)
                ->pluck('relief_options.id')
                ->map(fn ($id) => (int) $id);
        }

        return view('admin.settings.relief-options.index', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'reliefOptions' => $reliefOptions,
            'periodOptionIds' => $periodOptionIds,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('relief_options', 'name'),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],
        ]);

        $option = ReliefOption::create([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => true,
            'sort_order' => $validated['sort_order'],
        ]);

        $period = PpdbPeriod::findOrFail(
            $validated['period_id']
        );

        $period->reliefOptions()->syncWithoutDetaching([
            $option->id => [
                'is_active' => true,
                'sort_order' => $validated['sort_order'],
            ],
        ]);

        return redirect()
            ->route('admin.relief-options.index', [
                'period_id' => $period->id,
            ])
            ->with(
                'success',
                'Keringanan / Prestasi berhasil ditambahkan.'
            );
    }

    public function update(
        Request $request,
        ReliefOption $reliefOption
    ): RedirectResponse {
        $validated = $request->validate([
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('relief_options', 'name')
                    ->ignore($reliefOption->id),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],
        ]);

        $reliefOption->update([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'],
        ]);

        $period = PpdbPeriod::findOrFail(
            $validated['period_id']
        );

        if (
            $period->reliefOptions()
                ->where(
                    'relief_options.id',
                    $reliefOption->id
                )
                ->exists()
        ) {
            $period->reliefOptions()->updateExistingPivot(
                $reliefOption->id,
                [
                    'sort_order' => $validated['sort_order'],
                ]
            );
        }

        return redirect()
            ->route('admin.relief-options.index', [
                'period_id' => $period->id,
            ])
            ->with(
                'success',
                'Keringanan / Prestasi berhasil diperbarui.'
            );
    }

    public function toggleMaster(
        Request $request,
        ReliefOption $reliefOption
    ): RedirectResponse {
        $request->validate([
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],
        ]);

        $reliefOption->update([
            'is_active' => ! $reliefOption->is_active,
        ]);

        return redirect()
            ->route('admin.relief-options.index', [
                'period_id' => $request->integer('period_id'),
            ])
            ->with(
                'success',
                'Status master Keringanan / Prestasi berhasil diperbarui.'
            );
    }

    public function togglePeriod(
        Request $request,
        ReliefOption $reliefOption
    ): RedirectResponse {
        $validated = $request->validate([
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],
        ]);

        $period = PpdbPeriod::findOrFail(
            $validated['period_id']
        );

        $existing = $period->reliefOptions()
            ->where(
                'relief_options.id',
                $reliefOption->id
            )
            ->first();

        if (! $existing) {
            $period->reliefOptions()->attach(
                $reliefOption->id,
                [
                    'is_active' => true,
                    'sort_order' => $reliefOption->sort_order,
                ]
            );
        } else {
            $period->reliefOptions()->updateExistingPivot(
                $reliefOption->id,
                [
                    'is_active' => ! (bool) $existing->pivot->is_active,
                ]
            );
        }

        return redirect()
            ->route('admin.relief-options.index', [
                'period_id' => $period->id,
            ])
            ->with(
                'success',
                'Ketersediaan Keringanan / Prestasi pada periode berhasil diperbarui.'
            );
    }
}