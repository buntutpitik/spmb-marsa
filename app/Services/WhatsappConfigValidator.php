<?php

namespace App\Services;

use RuntimeException;

class WhatsappConfigValidator
{
    public function validate(): array
    {
        $enabled = (bool) config(
            'services.whatsapp.enabled',
            false
        );

        $config = [
            'enabled' => $enabled,
            'access_token' => config(
                'services.whatsapp.meta.access_token'
            ),
            'phone_number_id' => config(
                'services.whatsapp.meta.phone_number_id'
            ),
            'business_account_id' => config(
                'services.whatsapp.meta.business_account_id'
            ),
            'api_version' => config(
                'services.whatsapp.meta.api_version'
            ),
            'timeout' => config(
                'services.whatsapp.meta.timeout'
            ),
        ];

        /*
         * Kalau WA memang OFF, konfigurasi credential
         * tidak wajib lengkap.
         */
        if (! $enabled) {
            return [
                'ready' => false,
                'enabled' => false,
                'reason' => 'WhatsApp dinonaktifkan.',
            ];
        }

        $required = [
            'access_token',
            'phone_number_id',
            'business_account_id',
            'api_version',
        ];

        foreach ($required as $key) {
            if (
                ! isset($config[$key])
                || trim((string) $config[$key]) === ''
            ) {
                throw new RuntimeException(
                    "Konfigurasi WhatsApp {$key} belum lengkap."
                );
            }
        }

        return [
            'ready' => true,
            'enabled' => true,
            'api_version' => $config['api_version'],
            'timeout' => (int) $config['timeout'],
        ];
    }
}