<?php

namespace Database\Seeders;

use App\Models\PpdbPeriod;
use App\Models\ReliefOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class ReliefOptionSeeder extends Seeder
{
    public function run(): void
    {
        $period = PpdbPeriod::query()
            ->where('name', '2027/2028')
            ->first();

        if (! $period) {
            throw new RuntimeException(
                'Periode SPMB 2027/2028 tidak ditemukan.'
            );
        }

        $items = [
            'Yatim',
            'Yatim Piatu',
            "Alumni SMP/MTs Ma'arif",
            'Anak ke-3 dan seterusnya',
            'Anak Alumni',
            'Siswa bersaudara pada tahun ajaran yang sama',
            'Prestasi POPDA/Aksioma Tk. Nasional',
            'Prestasi POPDA/Aksioma Tk. Provinsi',
            'Prestasi POPDA/Aksioma Tk. Kabupaten',
            'Prestasi Akademik 10 besar lulusan terbaik Sekolah Negeri',
            'Prestasi Akademik 5 besar lulusan terbaik Sekolah Swasta',
            'Juara 1 Harlah MARSA',
            'Juara 2 Harlah MARSA',
        ];

        foreach ($items as $index => $name) {
            $reliefOption = ReliefOption::updateOrCreate(
                [
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );

            $period->reliefOptions()->syncWithoutDetaching([
                $reliefOption->id => [
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            ]);
        }
    }
}