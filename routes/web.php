<?php

use App\Http\Controllers\Admin\AdmissionController;
use App\Http\Controllers\Admin\AdmissionExportController;
use App\Http\Controllers\Admin\AdmissionPdfController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\MajorRecapExportController;
use App\Http\Controllers\Admin\MajorRecapPdfController;
use App\Http\Controllers\Admin\OriginSchoolController;
use App\Http\Controllers\Admin\OriginSchoolRecapController;
use App\Http\Controllers\Admin\OriginSchoolRecapExportController;
use App\Http\Controllers\Admin\OriginSchoolRecapPdfController;
use App\Http\Controllers\Admin\RecapController;
use App\Http\Controllers\Admin\ReenrollmentController;
use App\Http\Controllers\Admin\ReenrollmentFinanceExportController;
use App\Http\Controllers\Admin\ReenrollmentFinancePdfController;
use App\Http\Controllers\Admin\ReenrollmentFinanceRecapController;
use App\Http\Controllers\Admin\ReenrollmentPaymentController;
use App\Http\Controllers\Admin\ReferralRecapController;
use App\Http\Controllers\Admin\ReferralRecapExportController;
use App\Http\Controllers\Admin\ReferralRecapPdfController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\RegistrationExportController;
use App\Http\Controllers\Admin\RegistrationPdfController;
use App\Http\Controllers\Admin\RegistrationStatusController;
use App\Http\Controllers\Admin\ReliefOptionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SpecialProgramController;
use App\Http\Controllers\Admin\WhatsappLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get(
        '/login',
        [LoginController::class, 'create']
    )->name('login');

    Route::post(
        '/login',
        [LoginController::class, 'store']
    )
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post(
    '/logout',
    [LoginController::class, 'destroy']
)
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Public Registration
|--------------------------------------------------------------------------
*/

Route::get(
    '/daftar',
    [PublicRegistrationController::class, 'create']
)->name('registration.create');

Route::post(
    '/daftar',
    [PublicRegistrationController::class, 'store']
)->name('registration.store');

Route::get(
    '/pendaftaran/sukses/{publicToken}',
    [PublicRegistrationController::class, 'success']
)->name('registration.success');

/*
|--------------------------------------------------------------------------
| Internal Dashboard
|--------------------------------------------------------------------------
|
| Semua role internal boleh membuka dashboard.
|
*/

Route::get(
    '/',
    [DashboardController::class, 'index']
)
    ->middleware([
        'auth',
        'role:SUPERADMIN,ADMIN,PANITIA,BENDAHARA',
    ])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware('auth')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Operasional Umum
        |--------------------------------------------------------------------------
        |
        | BENDAHARA juga merangkap PANITIA.
        |
        | SUPERADMIN
        | ADMIN
        | PANITIA
        | BENDAHARA
        |
        */

        Route::middleware(
            'role:SUPERADMIN,ADMIN,PANITIA,BENDAHARA'
        )->group(function () {

            /*
             * Penerimaan
             */

            Route::get(
                '/penerimaan',
                [AdmissionController::class, 'index']
            )->name('admissions.index');

            /*
             * Daftar Ulang
             */

            Route::get(
                '/daftar-ulang',
                [ReenrollmentController::class, 'index']
            )->name('reenrollments.index');

            /*
             * Rekap Umum
             */

            Route::get(
                '/rekap',
                [RecapController::class, 'index']
            )->name('recaps.index');

            Route::get(
                '/rekap/asal-sekolah',
                [OriginSchoolRecapController::class, 'index']
            )->name('recaps.origin-schools.index');

            Route::get(
                '/rekap/referral',
                [ReferralRecapController::class, 'index']
            )->name('recaps.referrals.index');

            /*
             * Analitik
             */

            Route::get(
                '/analitik',
                [AnalyticsController::class, 'index']
            )->name('analytics.index');

            /*
             * Laporan
             */

            Route::get(
                '/laporan',
                [ReportController::class, 'index']
            )->name('reports.index');

            Route::get(
                '/laporan/pendaftar/excel',
                [RegistrationExportController::class, 'excel']
            )->name('reports.registrations.excel');

            Route::get(
                '/laporan/rekap-jurusan/excel',
                [MajorRecapExportController::class, 'excel']
            )->name('reports.major-recap.excel');

            Route::get(
                '/laporan/rekap-asal-sekolah/excel',
                [OriginSchoolRecapExportController::class, 'excel']
            )->name('reports.origin-school-recap.excel');

            Route::get(
                '/laporan/rekap-referral/excel',
                [ReferralRecapExportController::class, 'excel']
            )->name('reports.referral-recap.excel');

            Route::get(
                '/laporan/pendaftar/pdf',
                [RegistrationPdfController::class, 'download']
            )->name('reports.registrations.pdf');

            Route::get(
                '/laporan/penerimaan/pdf',
                [AdmissionPdfController::class, 'download']
            )->name('reports.admissions.pdf');

            Route::get(
                '/laporan/rekap-jurusan/pdf',
                [MajorRecapPdfController::class, 'download']
            )->name('reports.major-recap.pdf');

            Route::get(
                '/laporan/rekap-asal-sekolah/pdf',
                [OriginSchoolRecapPdfController::class, 'download']
            )->name('reports.origin-school-recap.pdf');

            Route::get(
                '/laporan/rekap-referral/pdf',
                [ReferralRecapPdfController::class, 'download']
            )->name('reports.referral-recap.pdf');

            Route::get(
                '/laporan/penerimaan/excel',
                [AdmissionExportController::class, 'excel']
            )->name('reports.admissions.excel');

            /*
             * WhatsApp
             */

            Route::get(
                '/whatsapp',
                [WhatsappLogController::class, 'index']
            )->name('whatsapp-logs.index');

            /*
             * Pendaftaran
             */

            Route::get(
                '/pendaftaran',
                [RegistrationController::class, 'index']
            )->name('registrations.index');

            Route::get(
                '/pendaftaran/{registration}',
                [RegistrationController::class, 'show']
            )->name('registrations.show');

            /*
             * Perubahan status / penerimaan.
             *
             * BENDAHARA tetap boleh karena BENDAHARA
             * juga merangkap PANITIA.
             */

            Route::patch(
                '/pendaftaran/{registration}/status',
                [RegistrationStatusController::class, 'update']
            )->name('registrations.status.update');
        });

        /*
        |--------------------------------------------------------------------------
        | Keuangan Daftar Ulang
        |--------------------------------------------------------------------------
        |
        | PANITIA biasa tidak memiliki akses keuangan.
        |
        | SUPERADMIN
        | ADMIN
        | BENDAHARA
        |
        */

        Route::middleware(
            'role:SUPERADMIN,ADMIN,BENDAHARA'
        )->group(function () {

            Route::post(
                '/pendaftaran/{registration}/pembayaran-daftar-ulang',
                [ReenrollmentPaymentController::class, 'store']
            )->name('registrations.reenrollment-payments.store');

            Route::get(
                '/rekap/keuangan-daftar-ulang',
                [ReenrollmentFinanceRecapController::class, 'index']
            )->name('recaps.reenrollment-finance.index');

            Route::get(
                '/laporan/daftar-ulang-keuangan/excel',
                [ReenrollmentFinanceExportController::class, 'excel']
            )->name('reports.reenrollment-finance.excel');

            Route::get(
                '/laporan/daftar-ulang-keuangan/pdf',
                [ReenrollmentFinancePdfController::class, 'download']
            )->name('reports.reenrollment-finance.pdf');
        });

        /*
        |--------------------------------------------------------------------------
        | Pengaturan / Master Data
        |--------------------------------------------------------------------------
        |
        | Hanya SUPERADMIN.
        |
        */

        Route::middleware(
            'role:SUPERADMIN'
        )->group(function () {

            /*
             * Users
             */

            Route::get(
                '/users',
                [UserController::class, 'index']
            )->name('users.index');

            Route::post(
                '/users',
                [UserController::class, 'store']
            )->name('users.store');

            Route::put(
                '/users/{user}',
                [UserController::class, 'update']
            )->name('users.update');

            Route::patch(
                '/users/{user}/toggle-active',
                [UserController::class, 'toggleActive']
            )->name('users.toggle-active');

            Route::patch(
                '/users/{user}/reset-password',
                [UserController::class, 'resetPassword']
            )->name('users.reset-password');

            /*
             * Pengaturan Utama
             */

            Route::get(
                '/pengaturan',
                [SettingsController::class, 'index']
            )->name('settings.index');

            /*
             * Keringanan / Prestasi
             */

            Route::get(
                '/pengaturan/keringanan',
                [ReliefOptionController::class, 'index']
            )->name('relief-options.index');

            Route::post(
                '/pengaturan/keringanan',
                [ReliefOptionController::class, 'store']
            )->name('relief-options.store');

            Route::put(
                '/pengaturan/keringanan/{reliefOption}',
                [ReliefOptionController::class, 'update']
            )->name('relief-options.update');

            Route::patch(
                '/pengaturan/keringanan/{reliefOption}/toggle-master',
                [ReliefOptionController::class, 'toggleMaster']
            )->name('relief-options.toggle-master');

            Route::patch(
                '/pengaturan/keringanan/{reliefOption}/toggle-periode',
                [ReliefOptionController::class, 'togglePeriod']
            )->name('relief-options.toggle-period');

            /*
             * Program Khusus
             */

            Route::get(
                '/pengaturan/program-khusus',
                [SpecialProgramController::class, 'index']
            )->name('special-programs.index');

            Route::post(
                '/pengaturan/program-khusus',
                [SpecialProgramController::class, 'store']
            )->name('special-programs.store');

            Route::put(
                '/pengaturan/program-khusus/{specialProgram}',
                [SpecialProgramController::class, 'update']
            )->name('special-programs.update');

            Route::patch(
                '/pengaturan/program-khusus/{specialProgram}/toggle-master',
                [SpecialProgramController::class, 'toggleMaster']
            )->name('special-programs.toggle-master');

            Route::patch(
                '/pengaturan/program-khusus/{specialProgram}/toggle-periode',
                [SpecialProgramController::class, 'togglePeriod']
            )->name('special-programs.toggle-period');

            /*
             * Master Asal Sekolah
             */

            Route::get(
                '/pengaturan/asal-sekolah',
                [OriginSchoolController::class, 'index']
            )->name('origin-schools.index');

            Route::post(
                '/pengaturan/asal-sekolah',
                [OriginSchoolController::class, 'store']
            )->name('origin-schools.store');

            Route::put(
                '/pengaturan/asal-sekolah/{originSchool}',
                [OriginSchoolController::class, 'update']
            )->name('origin-schools.update');

            Route::patch(
                '/pengaturan/asal-sekolah/{originSchool}/toggle',
                [OriginSchoolController::class, 'toggle']
            )->name('origin-schools.toggle');
        });
    });