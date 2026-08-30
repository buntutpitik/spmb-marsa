<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSpecialProgramRequest;
use App\Http\Requests\Admin\UpdateSpecialProgramRequest;
use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use App\Models\SpecialProgram;
use App\Services\PeriodContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SpecialProgramController extends Controller
{
    public function __construct(
        protected PeriodContext $periodContext
    ) {
    }

    public function index(Request $request): View
    {
        $periods = PpdbPeriod::query()
            ->whereNull('archived_at')
            ->orderByDesc('year_start')
            ->get();

        $selectedPeriod = $this->periodContext
            ->resolveAdminPeriod($request);

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

    public function store(
        StoreSpecialProgramRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        /*
         * Resolve periode sebelum mutation apa pun dilakukan.
         *
         * PeriodContext memastikan:
         * - period_id valid;
         * - periode bukan archived;
         * - fallback ke periode aktif tetap konsisten.
         */
        $period = $this->periodContext
            ->resolveWritableExplicitPeriod($request);

        $program = DB::transaction(function () use (
            $request,
            $validated,
            $period
        ) {
            $program = SpecialProgram::create([
                'name' => trim($validated['name']),
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'is_active' => true,
                'sort_order' => $validated['sort_order'],
            ]);

            $period->specialPrograms()->syncWithoutDetaching([
                $program->id => [
                    'is_active' => true,
                    'sort_order' => $validated['sort_order'],
                ],
            ]);

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => null,
                'action' => 'CREATE_SPECIAL_PROGRAM',
                'description' => 'Program Khusus ditambahkan.',
                'metadata' => [
                    'special_program_id' => $program->id,
                    'period_id' => $period->id,
                    'new' => $program->only([
                        'name',
                        'slug',
                        'description',
                        'is_active',
                        'sort_order',
                    ]),
                    'period' => [
                        'is_active' => true,
                        'sort_order' => $validated['sort_order'],
                    ],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return $program;
        });

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
        UpdateSpecialProgramRequest $request,
        SpecialProgram $specialProgram
    ): RedirectResponse {
        $validated = $request->validated();

        /*
         * Resolve period sebelum master maupun pivot dimutasi.
         */
        $period = $this->periodContext
            ->resolveWritableExplicitPeriod($request);

        $old = $specialProgram->only([
            'name',
            'slug',
            'description',
            'is_active',
            'sort_order',
        ]);

        $existing = $period->specialPrograms()
            ->where(
                'special_programs.id',
                $specialProgram->id
            )
            ->first();

        $oldPivot = $existing
            ? [
                'is_active' => (bool) $existing->pivot->is_active,
                'sort_order' => (int) $existing->pivot->sort_order,
            ]
            : null;

        DB::transaction(function () use (
            $request,
            $validated,
            $period,
            $specialProgram,
            $old,
            $existing,
            $oldPivot
        ) {
            $specialProgram->update([
                'name' => trim($validated['name']),
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'],
            ]);

            if ($existing) {
                $period->specialPrograms()->updateExistingPivot(
                    $specialProgram->id,
                    [
                        'sort_order' => $validated['sort_order'],
                    ]
                );
            }

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => null,
                'action' => 'UPDATE_SPECIAL_PROGRAM',
                'description' => 'Program Khusus diperbarui.',
                'metadata' => [
                    'special_program_id' => $specialProgram->id,
                    'period_id' => $period->id,
                    'old' => $old,
                    'new' => $specialProgram
                        ->fresh()
                        ->only([
                            'name',
                            'slug',
                            'description',
                            'is_active',
                            'sort_order',
                        ]),
                    'period_old' => $oldPivot,
                    'period_new' => $existing
                        ? [
                            'is_active' => $oldPivot['is_active'],
                            'sort_order' =>
                                (int) $validated['sort_order'],
                        ]
                        : null,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        });

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
        $validated = $request->validate([
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],
        ]);

        /*
         * Jangan izinkan mutation master dilakukan dari
         * context periode yang sudah archived.
         */
        $period = $this->periodContext
            ->resolveWritableExplicitPeriod($request);

        $oldStatus = (bool) $specialProgram->is_active;

        DB::transaction(function () use (
            $request,
            $period,
            $specialProgram,
            $oldStatus
        ) {
            $specialProgram->update([
                'is_active' => ! $oldStatus,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => null,
                'action' => 'TOGGLE_SPECIAL_PROGRAM',
                'description' =>
                    'Status master Program Khusus diperbarui.',
                'metadata' => [
                    'special_program_id' => $specialProgram->id,
                    'period_id' => $period->id,
                    'old' => [
                        'is_active' => $oldStatus,
                    ],
                    'new' => [
                        'is_active' =>
                            (bool) $specialProgram->fresh()->is_active,
                    ],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.special-programs.index', [
                'period_id' => $period->id,
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

        /*
         * Resolve melalui PeriodContext agar archived period
         * tidak dapat dimutasi.
         */
        $period = $this->periodContext
            ->resolveWritableExplicitPeriod($request);

        $existing = $period->specialPrograms()
            ->where(
                'special_programs.id',
                $specialProgram->id
            )
            ->first();

        $oldStatus = $existing
            ? (bool) $existing->pivot->is_active
            : null;

        DB::transaction(function () use (
            $request,
            $period,
            $specialProgram,
            $existing,
            $oldStatus
        ) {
            if (! $existing) {
                $period->specialPrograms()->attach(
                    $specialProgram->id,
                    [
                        'is_active' => true,
                        'sort_order' => $specialProgram->sort_order,
                    ]
                );

                $newStatus = true;
            } else {
                $newStatus = ! $oldStatus;

                $period->specialPrograms()->updateExistingPivot(
                    $specialProgram->id,
                    [
                        'is_active' => $newStatus,
                    ]
                );
            }

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => null,
                'action' => 'TOGGLE_PERIOD_SPECIAL_PROGRAM',
                'description' =>
                    'Ketersediaan Program Khusus pada periode diperbarui.',
                'metadata' => [
                    'special_program_id' => $specialProgram->id,
                    'period_id' => $period->id,
                    'old' => [
                        'is_active' => $oldStatus,
                    ],
                    'new' => [
                        'is_active' => $newStatus,
                    ],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        });

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