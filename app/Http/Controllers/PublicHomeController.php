<?php

namespace App\Http\Controllers;

use App\Models\PublicPageSetting;
use App\Models\School;
use App\Services\PeriodContext;
use App\Services\AdmissionPathResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\View\View;

class PublicHomeController extends Controller
{
    public function __construct(
        protected PeriodContext $periodContext,
        protected AdmissionPathResolver $admissionPathResolver
    ) {
    }

    public function index(): View
    {
        $period = $this->periodContext
            ->resolveActivePeriod();

        if ($period) {
            $period->load([
                'school',
                'admissionPaths' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),

                'majors' => fn ($query) => $query
                    ->wherePivot('is_active', true)
                    ->where('majors.is_active', true)
                    ->orderBy('majors.sort_order')
                    ->orderBy('majors.name'),

                'specialPrograms' => fn ($query) => $query
                    ->wherePivot('is_active', true)
                    ->where('special_programs.is_active', true)
                    ->orderByPivot('sort_order')
                    ->orderBy('special_programs.name'),
            ]);
        }

        $activePath = null;

        if ($period) {
            try {
                $activePath = $this->admissionPathResolver
                    ->resolve(
                        $period,
                        now()
                    );
            } catch (ModelNotFoundException) {
                $activePath = null;
            }
        }

        $registrationAvailable = $period !== null
            && $activePath !== null;

        $registrationState = 'UNAVAILABLE';

        if ($period) {
            $today = now()->startOfDay();

            if (
                $period->registration_open
                && $today->lt(
                    $period->registration_open->copy()->startOfDay()
                )
            ) {
                $registrationState = 'UPCOMING';
            } elseif (
                $period->registration_close
                && $today->gt(
                    $period->registration_close->copy()->endOfDay()
                )
            ) {
                $registrationState = 'CLOSED';
            } elseif ($registrationAvailable) {
                $registrationState = 'OPEN';
            }
        }

        $school = $period?->school
            ?? School::query()
                ->orderBy('id')
                ->firstOrFail();

        $setting = PublicPageSetting::query()
            ->where('school_id', $school->id)
            ->first();

        return view(
            'public.home',
            [
                'school' => $school,
                'period' => $period,
                'setting' => $setting,
                'requirements' => $this->lines(
                    $setting?->requirements
                ),
                'registrationSteps' => $this->lines(
                    $setting?->registration_steps
                ),
                'activePath' => $activePath,
                'registrationAvailable' => $registrationAvailable,
                'registrationState' => $registrationState,
            ]
        );
    }

    private function lines(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return collect(
            preg_split(
                '/\r\n|\r|\n/',
                $value
            )
        )
            ->map(
                fn ($line) => trim((string) $line)
            )
            ->filter()
            ->values()
            ->all();
    }
}