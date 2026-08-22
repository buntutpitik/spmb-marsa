<?php

namespace App\Services;

use InvalidArgumentException;

class WhatsappPhoneNormalizer
{
    public function normalize(string $phone): string
    {
        $phone = trim($phone);

        // Buang spasi, -, (, ), titik, dan karakter non-digit lainnya.
        $phone = preg_replace('/\D+/', '', $phone);

        if ($phone === null || $phone === '') {
            throw new InvalidArgumentException(
                'Nomor WhatsApp tidak boleh kosong.'
            );
        }

        // +62xxxxxxxxxx sudah menjadi 62xxxxxxxxxx
        if (str_starts_with($phone, '62')) {
            $normalized = $phone;
        }

        // 08xxxxxxxxxx -> 628xxxxxxxxxx
        elseif (str_starts_with($phone, '0')) {
            $normalized = '62' . substr($phone, 1);
        }

        // 8xxxxxxxxxx -> 628xxxxxxxxxx
        elseif (str_starts_with($phone, '8')) {
            $normalized = '62' . $phone;
        }

        else {
            throw new InvalidArgumentException(
                'Format nomor WhatsApp Indonesia tidak valid.'
            );
        }

        /*
         * Nomor Indonesia setelah normalisasi umumnya berada
         * dalam rentang yang masuk akal untuk nomor seluler.
         *
         * Format final:
         * 628xxxxxxxx
         */
        if (! preg_match('/^628\d{7,11}$/', $normalized)) {
            throw new InvalidArgumentException(
                'Nomor WhatsApp Indonesia tidak valid.'
            );
        }

        return $normalized;
    }
}