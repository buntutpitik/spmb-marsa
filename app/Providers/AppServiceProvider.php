<?php

namespace App\Providers;

use App\Services\PeriodContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
         * ---------------------------------------------------------
         * Local development time simulation.
         * ---------------------------------------------------------
         *
         * Hanya aktif jika:
         * - APP_ENV = local
         * - LOCAL_TEST_DATE memiliki nilai.
         *
         * Saat ini LOCAL_TEST_DATE kosong/null sehingga tidak
         * memengaruhi waktu aplikasi.
         */
        if (
            app()->environment('local')
            && config('app.local_test_date')
        ) {
            Carbon::setTestNow(
                Carbon::parse(
                    config('app.local_test_date'),
                    config('app.timezone')
                )
            );
        }

        /*
         * ---------------------------------------------------------
         * Shared active SPMB period untuk layout internal.
         * ---------------------------------------------------------
         */
        View::composer(
            'layouts.app',
            function ($view): void {
                $activePeriod = app(PeriodContext::class)
                    ->resolveActivePeriod();

                $view->with(
                    'activePeriod',
                    $activePeriod
                );
            }
        );
    }
}