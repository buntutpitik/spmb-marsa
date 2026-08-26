<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Services\PeriodContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RegistrationController extends Controller
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
        Registration $registration
    ): View {
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
        ]);
    }
}