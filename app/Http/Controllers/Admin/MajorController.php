<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMajorRequest;
use App\Http\Requests\Admin\UpdateMajorRequest;
use App\Http\Requests\Admin\UpdatePeriodMajorRequest;
use App\Models\ActivityLog;
use App\Models\Major;
use App\Models\PeriodMajor;
use App\Models\PpdbPeriod;
use App\Models\School;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MajorController extends Controller
{
    public function index(Request $request): View
    {
        $periods = PpdbPeriod::query()
            ->with('school')
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

        $school = $selectedPeriod?->school;

        if (! $school) {
            $school = School::query()
                ->orderBy('id')
                ->first();
        }

        $majors = collect();

        if ($school) {
            $majors = Major::query()
                ->where('school_id', $school->id)
                ->with([
                    'periodMajors' => function ($query) use (
                        $selectedPeriod
                    ) {
                        if ($selectedPeriod) {
                            $query->where(
                                'period_id',
                                $selectedPeriod->id
                            );
                        }
                    },
                ])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        return view(
            'admin.settings.majors.index',
            [
                'periods' => $periods,
                'selectedPeriod' => $selectedPeriod,
                'school' => $school,
                'majors' => $majors,
            ]
        );
    }

    public function store(
        StoreMajorRequest $request
    ): RedirectResponse {
        $period = PpdbPeriod::query()
            ->findOrFail(
                $request->integer('period_id')
            );

        $schoolId = $request->integer(
            'school_id'
        );

        if ($period->school_id !== $schoolId) {
            throw ValidationException::withMessages([
                'period_id' =>
                    'Periode tidak berasal dari sekolah yang dipilih.',
            ]);
        }

        $major = DB::transaction(function () use (
            $request,
            $period,
            $schoolId
        ) {
            $major = Major::query()->create([
                'school_id' => $schoolId,

                'code' =>
                    $request->validated('code'),

                'name' =>
                    $request->validated('name'),

                'short_name' =>
                    $request->validated('short_name'),

                'description' =>
                    $request->validated('description'),

                'icon_path' => null,

                'is_active' => true,

                'sort_order' =>
                    $request->integer('sort_order'),
            ]);

            $periodMajor = PeriodMajor::query()
                ->create([
                    'period_id' =>
                        $period->id,

                    'major_id' =>
                        $major->id,

                    'quota' =>
                        $request->validated('quota'),

                    'is_active' => true,
                ]);

            ActivityLog::query()->create([
                'user_id' =>
                    $request->user()?->id,

                'registration_id' => null,

                'action' =>
                    'CREATE_MAJOR',

                'description' =>
                    'Master jurusan ditambahkan.',

                'metadata' => [
                    'major_id' => $major->id,
                    'period_id' => $period->id,
                    'school_id' => $schoolId,

                    'new' => [
                        'major' =>
                            $major->only([
                                'school_id',
                                'code',
                                'name',
                                'short_name',
                                'description',
                                'is_active',
                                'sort_order',
                            ]),

                        'period_major' =>
                            $periodMajor->only([
                                'period_id',
                                'major_id',
                                'quota',
                                'is_active',
                            ]),
                    ],
                ],

                'ip_address' =>
                    $request->ip(),

                'user_agent' =>
                    $request->userAgent(),
            ]);

            return $major;
        });

        return redirect()
            ->route(
                'admin.majors.index',
                [
                    'period_id' =>
                        $period->id,
                ]
            )
            ->with(
                'success',
                "Jurusan {$major->code} berhasil ditambahkan."
            );
    }

    public function update(
        UpdateMajorRequest $request,
        Major $major
    ): RedirectResponse {
        $period = PpdbPeriod::query()
            ->findOrFail(
                $request->integer('period_id')
            );

        $this->ensureSameSchool(
            $major,
            $period
        );

        DB::transaction(function () use (
            $request,
            $major,
            $period
        ): void {
            $old = $major->only([
                'school_id',
                'code',
                'name',
                'short_name',
                'description',
                'is_active',
                'sort_order',
            ]);

            $major->update([
                'code' =>
                    $request->validated('code'),

                'name' =>
                    $request->validated('name'),

                'short_name' =>
                    $request->validated('short_name'),

                'description' =>
                    $request->validated('description'),

                'sort_order' =>
                    $request->integer('sort_order'),
            ]);

            ActivityLog::query()->create([
                'user_id' =>
                    $request->user()?->id,

                'registration_id' => null,

                'action' =>
                    'UPDATE_MAJOR',

                'description' =>
                    'Master jurusan diperbarui.',

                'metadata' => [
                    'major_id' => $major->id,
                    'period_id' => $period->id,
                    'school_id' => $major->school_id,

                    'old' => $old,

                    'new' =>
                        $major->fresh()->only([
                            'school_id',
                            'code',
                            'name',
                            'short_name',
                            'description',
                            'is_active',
                            'sort_order',
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
                'admin.majors.index',
                [
                    'period_id' =>
                        $period->id,
                ]
            )
            ->with(
                'success',
                'Master jurusan berhasil diperbarui.'
            );
    }

    public function toggleMaster(
        Request $request,
        Major $major
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

        $this->ensureSameSchool(
            $major,
            $period
        );

        DB::transaction(function () use (
            $request,
            $major,
            $period
        ): void {
            $old = $major->is_active;

            $major->update([
                'is_active' =>
                    ! $major->is_active,
            ]);

            ActivityLog::query()->create([
                'user_id' =>
                    $request->user()?->id,

                'registration_id' => null,

                'action' =>
                    'TOGGLE_MAJOR_MASTER',

                'description' =>
                    'Status master jurusan diperbarui.',

                'metadata' => [
                    'major_id' => $major->id,
                    'period_id' => $period->id,
                    'school_id' => $major->school_id,

                    'old' => [
                        'is_active' => $old,
                    ],

                    'new' => [
                        'is_active' =>
                            $major->fresh()->is_active,
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
                'admin.majors.index',
                [
                    'period_id' =>
                        $period->id,
                ]
            )
            ->with(
                'success',
                'Status master jurusan berhasil diperbarui.'
            );
    }

    public function updatePeriod(
        UpdatePeriodMajorRequest $request,
        Major $major
    ): RedirectResponse {
        $period = PpdbPeriod::query()
            ->findOrFail(
                $request->integer('period_id')
            );

        $this->ensureSameSchool(
            $major,
            $period
        );

        DB::transaction(function () use (
            $request,
            $major,
            $period
        ): void {
            $periodMajor = PeriodMajor::query()
                ->where(
                    'period_id',
                    $period->id
                )
                ->where(
                    'major_id',
                    $major->id
                )
                ->first();

            $old = $periodMajor
                ? $periodMajor->only([
                    'period_id',
                    'major_id',
                    'quota',
                    'is_active',
                ])
                : null;

            if (! $periodMajor) {
                $periodMajor = PeriodMajor::query()
                    ->create([
                        'period_id' =>
                            $period->id,

                        'major_id' =>
                            $major->id,

                        'quota' =>
                            $request->validated('quota'),

                        'is_active' =>
                            $request->boolean(
                                'is_active'
                            ),
                    ]);
            } else {
                $periodMajor->update([
                    'quota' =>
                        $request->validated('quota'),

                    'is_active' =>
                        $request->boolean(
                            'is_active'
                        ),
                ]);
            }

            ActivityLog::query()->create([
                'user_id' =>
                    $request->user()?->id,

                'registration_id' => null,

                'action' =>
                    'UPDATE_PERIOD_MAJOR',

                'description' =>
                    'Konfigurasi jurusan pada periode diperbarui.',

                'metadata' => [
                    'major_id' => $major->id,
                    'period_id' => $period->id,
                    'school_id' => $major->school_id,

                    'old' => $old,

                    'new' =>
                        $periodMajor
                            ->fresh()
                            ->only([
                                'period_id',
                                'major_id',
                                'quota',
                                'is_active',
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
                'admin.majors.index',
                [
                    'period_id' =>
                        $period->id,
                ]
            )
            ->with(
                'success',
                'Konfigurasi jurusan pada periode berhasil diperbarui.'
            );
    }

    private function ensureSameSchool(
        Major $major,
        PpdbPeriod $period
    ): void {
        if (
            $major->school_id
            !== $period->school_id
        ) {
            throw ValidationException::withMessages([
                'period_id' =>
                    'Jurusan dan periode berasal dari sekolah yang berbeda.',
            ]);
        }
    }
}