<?php

namespace App\Services;

use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\RegistrationSequence;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrationNumberService
{
    public function generate(PpdbPeriod $period, ?Major $major = null): string
    {
        if ($period->include_major_code && ! $major) {
            throw new InvalidArgumentException(
                'Major wajib diisi karena periode menggunakan kode jurusan.'
            );
        }

        if ($major && $major->school_id !== $period->school_id) {
            throw new InvalidArgumentException(
                'Jurusan tidak berasal dari sekolah yang sama dengan periode PPDB.'
            );
        }

        $sequenceKey = $period->include_major_code
            ? 'MAJOR:' . $major->id
            : 'GLOBAL';

        $majorId = $period->include_major_code
            ? $major->id
            : null;

        return DB::transaction(function () use (
            $period,
            $major,
            $majorId,
            $sequenceKey
        ) {
            /*
             * Pastikan row sequence tersedia.
             *
             * insertOrIgnore() + unique(period_id, sequence_key)
             * membuat first-create aman bila dua request datang bersamaan.
             */
            DB::table('registration_sequences')->insertOrIgnore([
                'period_id' => $period->id,
                'major_id' => $majorId,
                'sequence_key' => $sequenceKey,
                'current_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /*
             * Setelah row dipastikan ada, lock sampai transaction selesai.
             */
            $sequence = RegistrationSequence::query()
                ->where('period_id', $period->id)
                ->where('sequence_key', $sequenceKey)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->current_number++;
            $sequence->save();

            $number = str_pad(
                (string) $sequence->current_number,
                $period->number_digits,
                '0',
                STR_PAD_LEFT
            );

            $parts = [
                $period->number_prefix,
                $period->number_year,
            ];

            if ($period->include_major_code) {
                $parts[] = $major->code;
            }

            $parts[] = $number;

            return implode('-', $parts);
        });
    }
}