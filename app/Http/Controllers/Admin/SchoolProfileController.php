<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSchoolProfileRequest;
use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use App\Models\School;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SchoolProfileController extends Controller
{
    public function edit(): View
    {
        $school = $this->resolveSchool();

        return view(
            'admin.settings.school-profile.edit',
            [
                'school' => $school,
            ]
        );
    }

    public function update(
        UpdateSchoolProfileRequest $request
    ): RedirectResponse {
        $school = $this->resolveSchool();

        DB::transaction(function () use (
            $request,
            $school
        ) {
            $old = $school->only([
                'name',
                'npsn',
                'address',
                'village',
                'district',
                'city',
                'province',
                'postal_code',
                'phone',
                'whatsapp',
                'email',
                'website',
            ]);

            $school->update([
                'name' =>
                    $request->validated('name'),

                'npsn' =>
                    $request->validated('npsn'),

                'address' =>
                    $request->validated('address'),

                'village' =>
                    $request->validated('village'),

                'district' =>
                    $request->validated('district'),

                'city' =>
                    $request->validated('city'),

                'province' =>
                    $request->validated('province'),

                'postal_code' =>
                    $request->validated('postal_code'),

                'phone' =>
                    $request->validated('phone'),

                'whatsapp' =>
                    $request->validated('whatsapp'),

                'email' =>
                    $request->validated('email'),

                'website' =>
                    $request->validated('website'),
            ]);

            ActivityLog::create([
                'user_id' =>
                    $request->user()?->id,

                'registration_id' => null,

                'action' =>
                    'UPDATE_SCHOOL_PROFILE',

                'description' =>
                    'Profil sekolah diperbarui.',

                'metadata' => [
                    'school_id' => $school->id,
                    'old' => $old,
                    'new' => $school->fresh()->only([
                        'name',
                        'npsn',
                        'address',
                        'village',
                        'district',
                        'city',
                        'province',
                        'postal_code',
                        'phone',
                        'whatsapp',
                        'email',
                        'website',
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
                'admin.school-profile.edit'
            )
            ->with(
                'success',
                'Profil sekolah berhasil diperbarui.'
            );
    }

    private function resolveSchool(): School
    {
        $activePeriod = PpdbPeriod::query()
            ->with('school')
            ->where('is_active', true)
            ->where('status', 'OPEN')
            ->whereNull('archived_at')
            ->orderByDesc('year_start')
            ->first();

        if ($activePeriod?->school) {
            return $activePeriod->school;
        }

        return School::query()
            ->orderBy('id')
            ->firstOrFail();
    }
}