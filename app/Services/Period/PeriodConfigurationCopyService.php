<?php

namespace App\Services\Period;

use App\Models\AdmissionPath;
use App\Models\PpdbPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class PeriodConfigurationCopyService
{
    public function copy(
        PpdbPeriod $source,
        PpdbPeriod $target
    ): void {
        if ($source->school_id !== $target->school_id) {
            throw ValidationException::withMessages([
                'copy_from_period_id' =>
                    'Periode sumber harus berasal dari sekolah yang sama.',
            ]);
        }

        if ($source->id === $target->id) {
            throw ValidationException::withMessages([
                'copy_from_period_id' =>
                    'Periode sumber dan tujuan tidak boleh sama.',
            ]);
        }

        $target->update([
            'show_graduation_score' => $source->show_graduation_score,
            'enable_achievement_relief' => $source->enable_achievement_relief,
        ]);

        $yearDifference = $target->year_start - $source->year_start;

        $paths = AdmissionPath::query()
            ->where('period_id', $source->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($paths as $path) {
            AdmissionPath::query()->create([
                'period_id' => $target->id,
                'name' => $path->name,
                'code' => $path->code,
                'start_date' => $this->shiftDate(
                    $path->start_date,
                    $yearDifference
                ),
                'end_date' => $this->shiftDate(
                    $path->end_date,
                    $yearDifference
                ),
                'is_active' => $path->is_active,
                'sort_order' => $path->sort_order,
                'description' => $path->description,
            ]);
        }

        // Salin seluruh jurusan periode, termasuk yang nonaktif.
        // Pertahankan nilai kuota NULL dan status asli.
        $periodMajors = \Illuminate\Support\Facades\DB::table('period_majors')
            ->where('period_id', $source->id)
            ->orderBy('id')
            ->get();

        foreach ($periodMajors as $periodMajor) {
            \Illuminate\Support\Facades\DB::table('period_majors')
                ->insert([
                    'period_id' => $target->id,
                    'major_id' => $periodMajor->major_id,
                    'quota' => $periodMajor->quota,
                    'is_active' => $periodMajor->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        // Salin seluruh opsi keringanan periode.
        // Termasuk opsi nonaktif dan urutan tampilannya.
        $periodReliefOptions = \Illuminate\Support\Facades\DB::table(
            'period_relief_options'
        )
            ->where('ppdb_period_id', $source->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($periodReliefOptions as $reliefOption) {
            \Illuminate\Support\Facades\DB::table(
                'period_relief_options'
            )->insert([
                'ppdb_period_id' => $target->id,
                'relief_option_id' => $reliefOption->relief_option_id,
                'is_active' => $reliefOption->is_active,
                'sort_order' => $reliefOption->sort_order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Salin seluruh program khusus periode.
        // Pertahankan status aktif/nonaktif dan urutan tampilan.
        $periodSpecialPrograms = \Illuminate\Support\Facades\DB::table(
            'period_special_programs'
        )
            ->where('ppdb_period_id', $source->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($periodSpecialPrograms as $specialProgram) {
            \Illuminate\Support\Facades\DB::table(
                'period_special_programs'
            )->insert([
                'ppdb_period_id' => $target->id,
                'special_program_id' => $specialProgram->special_program_id,
                'is_active' => $specialProgram->is_active,
                'sort_order' => $specialProgram->sort_order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function shiftDate(
        mixed $date,
        int $yearDifference
    ): ?string {
        if ($date === null) {
            return null;
        }

        $original = CarbonImmutable::parse($date);
        $targetYear = $original->year + $yearDifference;

        // Hindari perubahan diam-diam 29 Februari menjadi 28 Februari.
        if (
            $original->month === 2
            && $original->day === 29
            && ! CarbonImmutable::create(
                $targetYear,
                1,
                1
            )->isLeapYear()
        ) {
            throw ValidationException::withMessages([
                'copy_from_period_id' =>
                    'Tanggal 29 Februari pada jalur sumber tidak valid '
                    . 'untuk tahun tujuan. Sesuaikan konfigurasi sumber.',
            ]);
        }

        return $original
            ->addYearsWithOverflow(0)
            ->setYear($targetYear)
            ->toDateString();
    }
}
