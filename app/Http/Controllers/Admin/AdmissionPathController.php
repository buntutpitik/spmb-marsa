<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdmissionPathRequest;
use App\Http\Requests\Admin\UpdateAdmissionPathRequest;
use App\Models\ActivityLog;
use App\Models\AdmissionPath;
use App\Models\PpdbPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionPathController extends Controller
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

        $admissionPaths = collect();

        if ($selectedPeriod) {
            $admissionPaths = AdmissionPath::query()
                ->where(
                    'period_id',
                    $selectedPeriod->id
                )
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return view(
            'admin.settings.admission-paths.index',
            [
                'periods' => $periods,
                'selectedPeriod' => $selectedPeriod,
                'admissionPaths' => $admissionPaths,
            ]
        );
    }

    public function store(
        StoreAdmissionPathRequest $request
    ): RedirectResponse {
        $period = PpdbPeriod::query()
            ->findOrFail(
                $request->integer('period_id')
            );

        $admissionPath = DB::transaction(
            function () use (
                $request,
                $period
            ) {
                $admissionPath =
                    AdmissionPath::query()->create([
                        'period_id' =>
                            $period->id,

                        'name' =>
                            $request->validated('name'),

                        'code' =>
                            $request->validated('code'),

                        'start_date' =>
                            $request->validated(
                                'start_date'
                            ),

                        'end_date' =>
                            $request->validated(
                                'end_date'
                            ),

                        'is_active' => true,

                        'sort_order' =>
                            $request->integer(
                                'sort_order'
                            ),

                        'description' =>
                            $request->validated(
                                'description'
                            ),
                    ]);

                ActivityLog::query()->create([
                    'user_id' =>
                        $request->user()?->id,

                    'registration_id' => null,

                    'action' =>
                        'CREATE_ADMISSION_PATH',

                    'description' =>
                        'Jalur pendaftaran ditambahkan.',

                    'metadata' => [
                        'admission_path_id' =>
                            $admissionPath->id,

                        'period_id' =>
                            $period->id,

                        'new' =>
                            $admissionPath->only([
                                'period_id',
                                'name',
                                'code',
                                'start_date',
                                'end_date',
                                'is_active',
                                'sort_order',
                                'description',
                            ]),
                    ],

                    'ip_address' =>
                        $request->ip(),

                    'user_agent' =>
                        $request->userAgent(),
                ]);

                return $admissionPath;
            }
        );

        return redirect()
            ->route(
                'admin.admission-paths.index',
                [
                    'period_id' =>
                        $period->id,
                ]
            )
            ->with(
                'success',
                "Jalur {$admissionPath->code} berhasil ditambahkan."
            );
    }

    public function update(
        UpdateAdmissionPathRequest $request,
        AdmissionPath $admissionPath
    ): RedirectResponse {
        $period = PpdbPeriod::query()
            ->findOrFail(
                $request->integer('period_id')
            );

        $this->ensureSamePeriod(
            $admissionPath,
            $period
        );

        DB::transaction(function () use (
            $request,
            $admissionPath,
            $period
        ): void {
            $old = $admissionPath->only([
                'period_id',
                'name',
                'code',
                'start_date',
                'end_date',
                'is_active',
                'sort_order',
                'description',
            ]);

            $admissionPath->update([
                'name' =>
                    $request->validated('name'),

                'code' =>
                    $request->validated('code'),

                'start_date' =>
                    $request->validated(
                        'start_date'
                    ),

                'end_date' =>
                    $request->validated(
                        'end_date'
                    ),

                'sort_order' =>
                    $request->integer(
                        'sort_order'
                    ),

                'description' =>
                    $request->validated(
                        'description'
                    ),
            ]);

            ActivityLog::query()->create([
                'user_id' =>
                    $request->user()?->id,

                'registration_id' => null,

                'action' =>
                    'UPDATE_ADMISSION_PATH',

                'description' =>
                    'Jalur pendaftaran diperbarui.',

                'metadata' => [
                    'admission_path_id' =>
                        $admissionPath->id,

                    'period_id' =>
                        $period->id,

                    'old' => $old,

                    'new' =>
                        $admissionPath
                            ->fresh()
                            ->only([
                                'period_id',
                                'name',
                                'code',
                                'start_date',
                                'end_date',
                                'is_active',
                                'sort_order',
                                'description',
                            ]),
                ],

                'ip_address' =>
                    $request->ip(),

                'user_agent' =>
                    $request->userAgent(),
            ]);
        });

        return redirect()
            ->route(
                'admin.admission-paths.index',
                [
                    'period_id' =>
                        $period->id,
                ]
            )
            ->with(
                'success',
                'Jalur pendaftaran berhasil diperbarui.'
            );
    }

    public function toggle(
        Request $request,
        AdmissionPath $admissionPath
    ): RedirectResponse {
        $validated = $request->validate([
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],
        ]);

        $period = PpdbPeriod::query()
            ->findOrFail(
                $validated['period_id']
            );

        $this->ensureSamePeriod(
            $admissionPath,
            $period
        );

        /*
         * Saat mengaktifkan jalur yang sebelumnya nonaktif,
         * pastikan rentangnya tidak bertabrakan dengan
         * jalur aktif lain dalam periode yang sama.
         */
        if (
            ! $admissionPath->is_active
            && $this->hasOverlap(
                $admissionPath
            )
        ) {
            throw ValidationException::withMessages([
                'start_date' =>
                    'Jalur tidak dapat diaktifkan karena rentang tanggal bertabrakan dengan jalur aktif lain pada periode ini.',
            ]);
        }

        DB::transaction(function () use (
            $request,
            $admissionPath,
            $period
        ): void {
            $old = [
                'is_active' =>
                    $admissionPath->is_active,
            ];

            $admissionPath->update([
                'is_active' =>
                    ! $admissionPath->is_active,
            ]);

            ActivityLog::query()->create([
                'user_id' =>
                    $request->user()?->id,

                'registration_id' => null,

                'action' =>
                    'TOGGLE_ADMISSION_PATH',

                'description' =>
                    'Status jalur pendaftaran diperbarui.',

                'metadata' => [
                    'admission_path_id' =>
                        $admissionPath->id,

                    'period_id' =>
                        $period->id,

                    'old' => $old,

                    'new' => [
                        'is_active' =>
                            $admissionPath
                                ->fresh()
                                ->is_active,
                    ],
                ],

                'ip_address' =>
                    $request->ip(),

                'user_agent' =>
                    $request->userAgent(),
            ]);
        });

        return redirect()
            ->route(
                'admin.admission-paths.index',
                [
                    'period_id' =>
                        $period->id,
                ]
            )
            ->with(
                'success',
                'Status jalur pendaftaran berhasil diperbarui.'
            );
    }

    private function ensureSamePeriod(
        AdmissionPath $admissionPath,
        PpdbPeriod $period
    ): void {
        if (
            $admissionPath->period_id
            !== $period->id
        ) {
            throw ValidationException::withMessages([
                'period_id' =>
                    'Jalur pendaftaran tidak berasal dari periode yang dipilih.',
            ]);
        }
    }

    private function hasOverlap(
        AdmissionPath $admissionPath
    ): bool {
        $startDate =
            $admissionPath->start_date
                ?->toDateString();

        $endDate =
            $admissionPath->end_date
                ?->toDateString();

        return AdmissionPath::query()
            ->where(
                'period_id',
                $admissionPath->period_id
            )
            ->where('is_active', true)
            ->whereKeyNot(
                $admissionPath->id
            )
            ->where(function ($query) use (
                $endDate
            ) {
                if ($endDate === null) {
                    return;
                }

                $query
                    ->whereNull('start_date')
                    ->orWhereDate(
                        'start_date',
                        '<=',
                        $endDate
                    );
            })
            ->where(function ($query) use (
                $startDate
            ) {
                if ($startDate === null) {
                    return;
                }

                $query
                    ->whereNull('end_date')
                    ->orWhereDate(
                        'end_date',
                        '>=',
                        $startDate
                    );
            })
            ->exists();
    }
}