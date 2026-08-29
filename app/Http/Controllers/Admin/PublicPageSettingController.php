<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePublicPageSettingRequest;
use App\Models\ActivityLog;
use App\Models\PublicPageSetting;
use App\Models\School;
use App\Services\PeriodContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PublicPageSettingController extends Controller
{
    public function __construct(
        protected PeriodContext $periodContext
    ) {
    }

    public function edit(): View
    {
        $school = $this->resolveSchool();

        $setting = $school->publicPageSetting
            ?? new PublicPageSetting([
                'school_id' => $school->id,
                'show_announcement' => true,
                'show_requirements' => true,
                'show_registration_steps' => true,
                'show_reenrollment_information' => true,
                'show_contact' => true,
            ]);

        return view(
            'admin.settings.public-page.edit',
            [
                'school' => $school,
                'setting' => $setting,
            ]
        );
    }

    public function update(
        UpdatePublicPageSettingRequest $request
    ): RedirectResponse {
        $school = $this->resolveSchool();

        DB::transaction(function () use (
            $request,
            $school
        ): void {
            $setting = PublicPageSetting::query()
                ->firstOrNew([
                    'school_id' => $school->id,
                ]);

            $old = $setting->exists
                ? $setting->only([
                    'hero_title',
                    'hero_subtitle',
                    'hero_description',
                    'announcement_title',
                    'announcement_body',
                    'show_announcement',
                    'requirements',
                    'show_requirements',
                    'registration_steps',
                    'show_registration_steps',
                    'reenrollment_information',
                    'show_reenrollment_information',
                    'show_contact',
                ])
                : null;

            $setting->fill([
                'hero_title' =>
                    $request->validated('hero_title'),

                'hero_subtitle' =>
                    $request->validated('hero_subtitle'),

                'hero_description' =>
                    $request->validated('hero_description'),

                'announcement_title' =>
                    $request->validated('announcement_title'),

                'announcement_body' =>
                    $request->validated('announcement_body'),

                'show_announcement' =>
                    $request->boolean('show_announcement'),

                'requirements' =>
                    $request->validated('requirements'),

                'show_requirements' =>
                    $request->boolean('show_requirements'),

                'registration_steps' =>
                    $request->validated('registration_steps'),

                'show_registration_steps' =>
                    $request->boolean(
                        'show_registration_steps'
                    ),

                'reenrollment_information' =>
                    $request->validated(
                        'reenrollment_information'
                    ),

                'show_reenrollment_information' =>
                    $request->boolean(
                        'show_reenrollment_information'
                    ),

                'show_contact' =>
                    $request->boolean('show_contact'),
            ]);

            $setting->school_id = $school->id;
            $setting->save();

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => null,

                'action' =>
                    'UPDATE_PUBLIC_PAGE_SETTING',

                'description' =>
                    'Pengaturan halaman publik diperbarui.',

                'metadata' => [
                    'school_id' => $school->id,
                    'old' => $old,

                    'new' => $setting
                        ->fresh()
                        ->only([
                            'hero_title',
                            'hero_subtitle',
                            'hero_description',
                            'announcement_title',
                            'announcement_body',
                            'show_announcement',
                            'requirements',
                            'show_requirements',
                            'registration_steps',
                            'show_registration_steps',
                            'reenrollment_information',
                            'show_reenrollment_information',
                            'show_contact',
                        ]),
                ],

                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('admin.public-page.edit')
            ->with(
                'success',
                'Pengaturan halaman publik berhasil diperbarui.'
            );
    }

    private function resolveSchool(): School
    {
        $activePeriod = $this->periodContext
            ->resolveActivePeriod();

        if ($activePeriod) {
            $activePeriod->loadMissing('school');
        }

        if ($activePeriod?->school) {
            return $activePeriod->school;
        }

        return School::query()
            ->orderBy('id')
            ->firstOrFail();
    }
}