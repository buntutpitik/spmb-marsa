<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Services\PeriodContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdmissionController extends Controller
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

        $majors = collect();
        $admissionPaths = collect();

        $counts = [
            'REGISTERED' => 0,
            'ACCEPTED' => 0,
            'REJECTED' => 0,
            'WITHDRAWN' => 0,
        ];

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

            $baseQuery = Registration::query()
                ->where('period_id', $selectedPeriod->id);

            foreach (array_keys($counts) as $status) {
                $counts[$status] = (clone $baseQuery)
                    ->where('status', $status)
                    ->count();
            }

            $query = Registration::query()
                ->with([
                    'major',
                    'admissionPath',
                ])
                ->where('period_id', $selectedPeriod->id)
                ->whereIn('status', [
                    'REGISTERED',
                    'ACCEPTED',
                    'REJECTED',
                    'WITHDRAWN',
                ]);

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
                        );
                });
            }

            if (
                $request->filled('status')
                && in_array(
                    $request->input('status'),
                    [
                        'REGISTERED',
                        'ACCEPTED',
                        'REJECTED',
                        'WITHDRAWN',
                    ],
                    true
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

        return view('admin.admissions.index', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'majors' => $majors,
            'admissionPaths' => $admissionPaths,
            'counts' => $counts,
            'registrations' => $registrations,
        ]);
    }
}