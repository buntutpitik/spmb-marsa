<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdateRegistrationRequest;
use App\Models\ActivityLog;
use App\Models\OriginSchool;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\AdmissionPath;
use App\Services\PeriodContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateRegistrationRequest;
use App\Services\RegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function __construct(
        protected PeriodContext $periodContext,
        protected RegistrationService $registrationService
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

        $statuses = [
            'REGISTERED' => 'Terdaftar',
            'ACCEPTED' => 'Diterima',
            'REJECTED' => 'Ditolak',
            'REENROLLED' => 'Daftar Ulang',
            'WITHDRAWN' => 'Mengundurkan Diri',
        ];

        $majors = collect();
        $admissionPaths = collect();

        $registrations = Registration::query()
            ->whereRaw('1 = 0')
            ->paginate(10);

        if ($selectedPeriod) {
            $majors = $selectedPeriod->majors()
                ->wherePivot('is_active', true)
                ->orderBy('majors.sort_order')
                ->orderBy('majors.name')
                ->get();

            $admissionPaths = $selectedPeriod
                ->admissionPaths()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $query = Registration::query()
                ->with([
                    'major',
                    'admissionPath',
                    'creator',
                ])
                ->where(
                    'period_id',
                    $selectedPeriod->id
                );

            if ($request->filled('q')) {
                $keyword = trim(
                    (string) $request->input('q')
                );

                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery
                        ->where(
                            'registration_number',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhere(
                            'nik',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhere(
                            'nisn',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhere(
                            'full_name',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhere(
                            'origin_school',
                            'like',
                            "%{$keyword}%"
                        )
                        ->orWhere(
                            'whatsapp',
                            'like',
                            "%{$keyword}%"
                        );
                });
            }

            if (
                $request->filled('status')
                && array_key_exists(
                    $request->input('status'),
                    $statuses
                )
            ) {
                $query->where(
                    'status',
                    $request->input('status')
                );
            }

            if ($request->filled('major_id')) {
                $query->where(
                    'major_id',
                    $request->integer('major_id')
                );
            }

            if ($request->filled('admission_path_id')) {
                $query->where(
                    'admission_path_id',
                    $request->integer('admission_path_id')
                );
            }

            $registrations = $query
                ->latest('registered_at')
                ->latest('id')
                ->paginate(10)
                ->withQueryString();
        }

        return view('admin.registrations.index', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'statuses' => $statuses,
            'majors' => $majors,
            'admissionPaths' => $admissionPaths,
            'registrations' => $registrations,
        ]);
    }

    public function show(
        Request $request,
        Registration $registration
    ): View {
        $selectedPeriod = $this->periodContext
            ->resolveAdminPeriod($request);

        abort_unless(
            $selectedPeriod
            && $registration->period_id === $selectedPeriod->id,
            404
        );
        $registration->load([
            'period',
            'major',
            'admissionPath',
            'creator',
            'reliefOptions',
            'specialPrograms',

            'statusHistories' => fn ($query) =>
                $query->latest('changed_at'),

            'reenrollmentPayments',

            'whatsappLogs' => fn ($query) =>
                $query->latest('id'),
        ]);

        return view('admin.registrations.show', [
            'registration' => $registration,
            'selectedPeriod' => $selectedPeriod,
        ]);
    }

    public function create(Request $request): View
    {
        $selectedPeriod = $this->periodContext
            ->resolveAdminPeriod($request);

        /*
        * Tambah pendaftar hanya diperbolehkan pada
        * periode aktif yang berstatus OPEN.
        *
        * Berbeda dengan PUBLIC:
        * tanggal registration_open / registration_close
        * tidak digunakan sebagai pembatas input petugas.
        */
        abort_unless(
            $selectedPeriod
            && $selectedPeriod->is_active
            && $selectedPeriod->status === 'OPEN',
            404
        );

        $majors = $selectedPeriod
            ->majors()
            ->wherePivot('is_active', true)
            ->where('majors.is_active', true)
            ->orderBy('majors.name')
            ->get();

        $originSchools = OriginSchool::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $reliefOptions = $selectedPeriod
            ->reliefOptions()
            ->wherePivot('is_active', true)
            ->where('relief_options.is_active', true)
            ->orderBy('period_relief_options.sort_order')
            ->orderBy('relief_options.name')
            ->get();

        $specialPrograms = $selectedPeriod
            ->specialPrograms()
            ->wherePivot('is_active', true)
            ->where('special_programs.is_active', true)
            ->orderBy('period_special_programs.sort_order')
            ->orderBy('special_programs.name')
            ->get();

        return view(
            'admin.registrations.create',
            compact(
                'selectedPeriod',
                'majors',
                'originSchools',
                'reliefOptions',
                'specialPrograms'
            )
        );
    }

    public function edit(
        Request $request,
        Registration $registration
    ): View {
        $selectedPeriod = $this->periodContext
            ->resolveAdminPeriod($request);

        abort_unless(
            $selectedPeriod
            && $registration->period_id === $selectedPeriod->id,
            404
        );

        /*
        * Periode historis / CLOSED selalu read-only.
        */
        abort_unless(
            $selectedPeriod->status === 'OPEN'
            && $selectedPeriod->is_active,
            404
        );

        $registration->load([
            'period',
            'major',
            'admissionPath',
            'reliefOptions',
            'specialPrograms',
        ]);

        $majors = $selectedPeriod->majors()
            ->where('majors.is_active', true)
            ->wherePivot('is_active', true)
            ->orderBy('majors.sort_order')
            ->orderBy('majors.name')
            ->get();

        $admissionPaths = $selectedPeriod
            ->admissionPaths()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $reliefOptions = $selectedPeriod
            ->reliefOptions()
            ->where('relief_options.is_active', true)
            ->wherePivot('is_active', true)
            ->orderBy('period_relief_options.sort_order')
            ->orderBy('relief_options.name')
            ->get();

        $specialPrograms = $selectedPeriod
            ->specialPrograms()
            ->where('special_programs.is_active', true)
            ->wherePivot('is_active', true)
            ->orderBy('period_special_programs.sort_order')
            ->orderBy('special_programs.name')
            ->get();

        $originSchools = OriginSchool::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.registrations.edit', [
            'registration' => $registration,
            'selectedPeriod' => $selectedPeriod,
            'majors' => $majors,
            'admissionPaths' => $admissionPaths,
            'reliefOptions' => $reliefOptions,
            'specialPrograms' => $specialPrograms,
            'originSchools' => $originSchools,
        ]);
    }

    public function store(
        CreateRegistrationRequest $request
    ): RedirectResponse {
        /*
        * POST create tidak mempercayai period_id dari browser.
        *
        * Pendaftaran baru selalu masuk ke periode
        * aktif + OPEN yang ditentukan server.
        */
        $selectedPeriod = $this->periodContext
            ->resolveActivePeriod();

        abort_unless(
            $selectedPeriod
            && $selectedPeriod->is_active
            && $selectedPeriod->status === 'OPEN',
            404
        );

        $data = $request->validated();

        $data['period_id'] = $selectedPeriod->id;

        try {
            $registration = $this->registrationService->create(
                $data,
                $request->user(),
                null,
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );
        } catch (ModelNotFoundException $exception) {
            if ($exception->getModel() !== AdmissionPath::class) {
                throw $exception;
            }

            return back()
                ->withInput()
                ->withErrors([
                    'admission_path' =>
                        'Tidak ada jalur pendaftaran yang tersedia '
                        .'untuk periode aktif. Silakan periksa '
                        .'pengaturan jadwal jalur pendaftaran.',
                ]);
        }

        return redirect()
            ->route(
                'admin.registrations.show',
                [
                    'registration' => $registration,
                    'period_id' => $selectedPeriod->id,
                ]
            )
            ->with(
                'success',
                'Pendaftar berhasil ditambahkan.'
            );
    }

    public function update(
        UpdateRegistrationRequest $request,
        Registration $registration
    ): RedirectResponse {
        $selectedPeriod = $this->periodContext
            ->resolveAdminPeriod($request);

        abort_unless(
            $selectedPeriod
            && $registration->period_id === $selectedPeriod->id,
            404
        );

        abort_unless(
            $selectedPeriod->status === 'OPEN'
            && $selectedPeriod->is_active,
            404
        );

        $validated = $request->validated();

        /*
        * Validasi relasi terhadap PERIODE yang sedang diedit.
        *
        * exists:id saja tidak cukup karena ID dari periode lain
        * tidak boleh disisipkan melalui manipulated request.
        */
        $majorValid = $selectedPeriod->majors()
            ->where('majors.id', $validated['major_id'])
            ->where('majors.is_active', true)
            ->wherePivot('is_active', true)
            ->exists();

        abort_unless($majorValid, 422);

        $admissionPathValid = $selectedPeriod
            ->admissionPaths()
            ->whereKey($validated['admission_path_id'])
            ->where('is_active', true)
            ->exists();

        abort_unless($admissionPathValid, 422);

        $reliefIds = collect(
            $validated['relief_options'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($reliefIds->isNotEmpty()) {
            $validReliefIds = $selectedPeriod
                ->reliefOptions()
                ->where('relief_options.is_active', true)
                ->wherePivot('is_active', true)
                ->whereIn(
                    'relief_options.id',
                    $reliefIds->all()
                )
                ->pluck('relief_options.id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            abort_unless(
                $validReliefIds->count() === $reliefIds->count(),
                422
            );
        }

        $specialProgramIds = collect(
            $validated['special_programs'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($specialProgramIds->isNotEmpty()) {
            $validSpecialProgramIds = $selectedPeriod
                ->specialPrograms()
                ->where('special_programs.is_active', true)
                ->wherePivot('is_active', true)
                ->whereIn(
                    'special_programs.id',
                    $specialProgramIds->all()
                )
                ->pluck('special_programs.id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            abort_unless(
                $validSpecialProgramIds->count()
                    === $specialProgramIds->count(),
                422
            );
        }

        DB::transaction(function () use (
            $request,
            $registration,
            $validated,
            $reliefIds,
            $specialProgramIds
        ): void {
            $before = $registration->only([
                'nik',
                'nisn',
                'full_name',
                'birth_place',
                'birth_date',
                'gender',
                'religion',
                'origin_school',
                'hamlet',
                'rt',
                'rw',
                'village',
                'district',
                'city',
                'province',
                'postal_code',
                'father_name',
                'mother_name',
                'father_job',
                'mother_job',
                'whatsapp',
                'graduation_score',
                'admission_path_id',
                'major_id',
                'referrer_name',
                'referrer_source',
                'notes',
            ]);

            unset(
                $validated['relief_options'],
                $validated['special_programs']
            );

            $registration->update($validated);

            $registration->reliefOptions()
                ->sync($reliefIds->all());

            $registration->specialPrograms()
                ->sync($specialProgramIds->all());

            $registration->refresh();

            $after = $registration->only(
                array_keys($before)
            );

            $changes = [];

            foreach ($before as $field => $oldValue) {
                $newValue = $after[$field] ?? null;

                if ((string) $oldValue !== (string) $newValue) {
                    $changes[$field] = [
                        'before' => $oldValue,
                        'after' => $newValue,
                    ];
                }
            }

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => $registration->id,
                'action' => 'UPDATE_REGISTRATION',
                'description' => 'Data pendaftar diperbarui.',
                'metadata' => [
                    'registration_number' =>
                        $registration->registration_number,

                    'changes' => $changes,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        });

        return redirect()
            ->route(
                'admin.registrations.show',
                [
                    'registration' => $registration,
                    'period_id' => $selectedPeriod->id,
                ]
            )
            ->with(
                'success',
                'Data pendaftar berhasil diperbarui.'
            );
    }
}