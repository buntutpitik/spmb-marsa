<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicRegistrationRequest;
use App\Models\OriginSchool;
use App\Services\PeriodContext;
use App\Models\Registration;
use App\Services\AdmissionPathResolver;
use App\Services\RegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;

class PublicRegistrationController extends Controller
{
    public function __construct(
        protected RegistrationService $registrationService,
        protected AdmissionPathResolver $admissionPathResolver,
        protected PeriodContext $periodContext
    ) {
    }

    public function create(): View
    {
        $period = $this->periodContext
            ->resolveActivePeriod();

        $majors = collect();
        $reliefOptions = collect();
        $specialPrograms = collect();
        $activePath = null;
        $originSchools = OriginSchool::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($period) {
            $majors = $period->majors()
                ->wherePivot('is_active', true)
                ->where('majors.is_active', true)
                ->orderBy('majors.sort_order')
                ->orderBy('majors.name')
                ->get();

            $reliefOptions = $period->reliefOptions()
                ->wherePivot('is_active', true)
                ->where('relief_options.is_active', true)
                ->orderByPivot('sort_order')
                ->orderBy('relief_options.name')
                ->get();

            $specialPrograms = $period->specialPrograms()
                ->wherePivot('is_active', true)
                ->where('special_programs.is_active', true)
                ->orderByPivot('sort_order')
                ->orderBy('special_programs.name')
                ->get();

            try {
                $activePath = $this->admissionPathResolver->resolve(
                    $period,
                    now()
                );
            } catch (ModelNotFoundException) {
                $activePath = null;
            }
        }

        return view('public.registration.create', [
            'period' => $period,
            'majors' => $majors,
            'reliefOptions' => $reliefOptions,
            'specialPrograms' => $specialPrograms,
            'activePath' => $activePath,
            'originSchools' => $originSchools,
        ]);
    }

    public function store(
        StorePublicRegistrationRequest $request
    ): RedirectResponse {
        $registration = $this->registrationService->create(
            $request->validated(),
            null,
            'Notifikasi pendaftaran berhasil.',
            [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return redirect()->route(
            'registration.success',
            [
                'publicToken' => $registration->public_token,
            ]
        );
    }

    public function success(
        string $publicToken
    ): View {
        $registration = Registration::query()
            ->with([
                'period',
                'admissionPath',
                'major',
                'reliefOptions',
                'specialPrograms',
            ])
            ->where('public_token', $publicToken)
            ->firstOrFail();

        return view(
            'public.registration.success',
            [
                'registration' => $registration,
            ]
        );
    }
}