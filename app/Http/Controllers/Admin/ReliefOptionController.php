<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReliefOptionRequest;
use App\Http\Requests\Admin\UpdateReliefOptionRequest;
use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use App\Models\ReliefOption;
use App\Services\PeriodContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReliefOptionController extends Controller
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

    public function store(
        StoreReliefOptionRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        /*
         * Resolve periode sebelum mutation apa pun.
         *
         * Ini penting supaya archived period menghasilkan
         * 404 sebelum master ReliefOption dibuat.
         */
        $period = $this->periodContext
            ->resolveAdminPeriod($request);

        $option = ReliefOption::create([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => true,
            'sort_order' => $validated['sort_order'],
        ]);

        $period->reliefOptions()->syncWithoutDetaching([
            $option->id => [
                'is_active' => true,
                'sort_order' => $validated['sort_order'],
            ],
        ]);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'registration_id' => null,
            'action' => 'CREATE_RELIEF_OPTION',
            'description' =>
                'Keringanan / Prestasi ditambahkan.',
            'metadata' => [
                'relief_option_id' => $option->id,
                'period_id' => $period->id,
                'new' => $option->only([
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
        UpdateReliefOptionRequest $request,
        ReliefOption $reliefOption
    ): RedirectResponse {
        $validated = $request->validated();

        /*
         * Resolve period sebelum master atau pivot dimutasi.
         */
        $period = $this->periodContext
            ->resolveAdminPeriod($request);

        $old = $reliefOption->only([
            'name',
            'slug',
            'description',
            'is_active',
            'sort_order',
        ]);

        $existing = $period->reliefOptions()
            ->where(
                'relief_options.id',
                $reliefOption->id
            )
            ->first();

        $oldPivot = $existing
            ? [
                'is_active' => (bool) $existing->pivot->is_active,
                'sort_order' => (int) $existing->pivot->sort_order,
            ]
            : null;

        $reliefOption->update([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'],
        ]);

        if ($existing) {
            $period->reliefOptions()->updateExistingPivot(
                $reliefOption->id,
                [
                    'sort_order' => $validated['sort_order'],
                ]
            );
        }

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'registration_id' => null,
            'action' => 'UPDATE_RELIEF_OPTION',
            'description' =>
                'Keringanan / Prestasi diperbarui.',
            'metadata' => [
                'relief_option_id' => $reliefOption->id,
                'period_id' => $period->id,
                'old' => $old,
                'new' => $reliefOption
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
        $validated = $request->validate([
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],
        ]);

        /*
         * Master global tetap harus dimutasi dari period
         * context yang masih selectable / non-archived.
         */
        $period = $this->periodContext
            ->resolveAdminPeriod($request);

        $oldStatus = (bool) $reliefOption->is_active;

        $reliefOption->update([
            'is_active' => ! $oldStatus,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'registration_id' => null,
            'action' => 'TOGGLE_RELIEF_OPTION',
            'description' =>
                'Status master Keringanan / Prestasi diperbarui.',
            'metadata' => [
                'relief_option_id' => $reliefOption->id,
                'period_id' => $period->id,
                'old' => [
                    'is_active' => $oldStatus,
                ],
                'new' => [
                    'is_active' =>
                        (bool) $reliefOption->fresh()->is_active,
                ],
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.relief-options.index', [
                'period_id' => $period->id,
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

        /*
         * Resolve melalui canonical PeriodContext agar
         * archived period tidak dapat dimutasi.
         */
        $period = $this->periodContext
            ->resolveAdminPeriod($request);

        $existing = $period->reliefOptions()
            ->where(
                'relief_options.id',
                $reliefOption->id
            )
            ->first();

        $oldStatus = $existing
            ? (bool) $existing->pivot->is_active
            : null;

        if (! $existing) {
            $period->reliefOptions()->attach(
                $reliefOption->id,
                [
                    'is_active' => true,
                    'sort_order' => $reliefOption->sort_order,
                ]
            );

            $newStatus = true;
        } else {
            $newStatus = ! $oldStatus;

            $period->reliefOptions()->updateExistingPivot(
                $reliefOption->id,
                [
                    'is_active' => $newStatus,
                ]
            );
        }

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'registration_id' => null,
            'action' => 'TOGGLE_PERIOD_RELIEF_OPTION',
            'description' =>
                'Ketersediaan Keringanan / Prestasi pada periode diperbarui.',
            'metadata' => [
                'relief_option_id' => $reliefOption->id,
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