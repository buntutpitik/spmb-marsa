<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSchoolProfileRequest;
use App\Models\ActivityLog;
use App\Models\School;
use App\Services\PeriodContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SchoolProfileController extends Controller
{
    public function __construct(
        protected PeriodContext $periodContext
    ) {
    }

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

        /*
         * Simpan path lama sebelum ada perubahan.
         */
        $oldLogoPath = $school->logo_path;
        $oldFaviconPath = $school->favicon_path;

        /*
         * Path file baru.
         *
         * Jika transaksi database gagal, file baru ini harus
         * dibersihkan agar tidak menjadi orphan file.
         */
        $newLogoPath = null;
        $newFaviconPath = null;

        try {
            /*
             * ---------------------------------------------------------
             * Upload file baru terlebih dahulu
             * ---------------------------------------------------------
             *
             * File lama BELUM dihapus pada tahap ini.
             */
            if ($request->hasFile('logo')) {
                $newLogoPath = $request
                    ->file('logo')
                    ->store(
                        "schools/{$school->id}/branding",
                        'public'
                    );
            }

            if ($request->hasFile('favicon')) {
                $newFaviconPath = $request
                    ->file('favicon')
                    ->store(
                        "schools/{$school->id}/branding",
                        'public'
                    );
            }

            DB::transaction(function () use (
                $request,
                $school,
                $newLogoPath,
                $newFaviconPath
            ): void {
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
                    'logo_path',
                    'favicon_path',
                ]);

                /*
                 * -----------------------------------------------------
                 * Tentukan branding final
                 * -----------------------------------------------------
                 *
                 * Prioritas:
                 *
                 * 1. file baru
                 * 2. checkbox hapus
                 * 3. pertahankan file lama
                 *
                 * Jadi jika file baru dikirim bersamaan dengan checkbox
                 * hapus, file baru tetap menang.
                 */
                $logoPath = $school->logo_path;

                if ($newLogoPath !== null) {
                    $logoPath = $newLogoPath;
                } elseif ($request->boolean('remove_logo')) {
                    $logoPath = null;
                }

                $faviconPath = $school->favicon_path;

                if ($newFaviconPath !== null) {
                    $faviconPath = $newFaviconPath;
                } elseif (
                    $request->boolean('remove_favicon')
                ) {
                    $faviconPath = null;
                }

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

                    'logo_path' =>
                        $logoPath,

                    'favicon_path' =>
                        $faviconPath,
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

                        'new' => $school
                            ->fresh()
                            ->only([
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
                                'logo_path',
                                'favicon_path',
                            ]),
                    ],

                    'ip_address' =>
                        $request->ip(),

                    'user_agent' =>
                        $request->userAgent(),
                ]);
            });
        } catch (Throwable $exception) {
            /*
             * ---------------------------------------------------------
             * Rollback filesystem
             * ---------------------------------------------------------
             *
             * DB::transaction sudah rollback database.
             * Bersihkan file BARU yang sempat tersimpan.
             */
            if ($newLogoPath !== null) {
                Storage::disk('public')
                    ->delete($newLogoPath);
            }

            if ($newFaviconPath !== null) {
                Storage::disk('public')
                    ->delete($newFaviconPath);
            }

            throw $exception;
        }

        /*
         * -------------------------------------------------------------
         * Cleanup file lama SETELAH transaksi sukses
         * -------------------------------------------------------------
         *
         * Hapus hanya jika path database sudah berubah.
         */
        $school->refresh();

        if (
            $oldLogoPath !== null
            && $oldLogoPath !== $school->logo_path
        ) {
            Storage::disk('public')
                ->delete($oldLogoPath);
        }

        if (
            $oldFaviconPath !== null
            && $oldFaviconPath !== $school->favicon_path
        ) {
            Storage::disk('public')
                ->delete($oldFaviconPath);
        }

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