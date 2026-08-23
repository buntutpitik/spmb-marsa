<?php

namespace App\Providers;

use App\Contracts\WhatsAppProvider;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            WhatsAppProvider::class,
            function () {
                $provider = config('whatsapp.provider', 'fake');

                $driver = config(
                    "whatsapp.providers.{$provider}.driver"
                );

                if (! $driver || ! class_exists($driver)) {
                    throw new InvalidArgumentException(
                        "WhatsApp provider [{$provider}] tidak valid."
                    );
                }

                return $this->app->make($driver);
            }
        );
    }

    public function boot(): void
    {
        //
    }
}