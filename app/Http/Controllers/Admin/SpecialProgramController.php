<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\SpecialProgram;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SpecialProgramController extends Controller
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

        $specialPrograms = SpecialProgram::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $periodProgramIds = collect();

        if ($selectedPeriod) {
            $periodProgramIds = $selectedPeriod
                ->specialPrograms()
                ->wherePivot('is_active', true)
                ->pluck('special_programs.id')
                ->map(fn ($id) => (int) $id);
        }

        return view('admin.settings.special-programs.index', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'specialPrograms' => $specialPrograms,
            'periodProgramIds' => $periodProgramIds,
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
                Rule::unique('special_programs', 'name'),
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

        $program = SpecialProgram::create([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => true,
            'sort_order' => $validated['sort_order'],
        ]);

        $period = PpdbPeriod::findOrFail(
            $validated['period_id']
        );

        $period->specialPrograms()->syncWithoutDetaching([
            $program->id => [
                'is_active' => true,
                'sort_order' => $validated['sort_order'],
            ],
        ]);

        return redirect()
            ->route('admin.special-programs.index', [
                'period_id' => $period->id,
            ])
            ->with(
                'success',
                'Program Khusus berhasil ditambahkan.'
            );
    }

    public function update(
        Request $request,
        SpecialProgram $specialProgram
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
                Rule::unique('special_programs', 'name')
                    ->ignore($specialProgram->id),
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

        $specialProgram->update([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'],
        ]);

        $period = PpdbPeriod::findOrFail(
            $validated['period_id']
        );

        if (
            $period->specialPrograms()
                ->where(
                    'special_programs.id',
                    $specialProgram->id
                )
                ->exists()
        ) {
            $period->specialPrograms()->updateExistingPivot(
                $specialProgram->id,
                [
                    'sort_order' => $validated['sort_order'],
                ]
            );
        }

        return redirect()
            ->route('admin.special-programs.index', [
                'period_id' => $period->id,
            ])
            ->with(
                'success',
                'Program Khusus berhasil diperbarui.'
            );
    }

    public function toggleMaster(
        Request $request,
        SpecialProgram $specialProgram
    ): RedirectResponse {
        $request->validate([
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],
        ]);

        $specialProgram->update([
            'is_active' => ! $specialProgram->is_active,
        ]);

        return redirect()
            ->route('admin.special-programs.index', [
                'period_id' => $request->integer('period_id'),
            ])
            ->with(
                'success',
                'Status master Program Khusus berhasil diperbarui.'
            );
    }

    public function togglePeriod(
        Request $request,
        SpecialProgram $specialProgram
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

        $existing = $period->specialPrograms()
            ->where(
                'special_programs.id',
                $specialProgram->id
            )
            ->first();

        if (! $existing) {
            $period->specialPrograms()->attach(
                $specialProgram->id,
                [
                    'is_active' => true,
                    'sort_order' => $specialProgram->sort_order,
                ]
            );
        } else {
            $period->specialPrograms()->updateExistingPivot(
                $specialProgram->id,
                [
                    'is_active' => ! (bool) $existing->pivot->is_active,
                ]
            );
        }

        return redirect()
            ->route('admin.special-programs.index', [
                'period_id' => $period->id,
            ])
            ->with(
                'success',
                'Ketersediaan Program Khusus pada periode berhasil diperbarui.'
            );
    }
}