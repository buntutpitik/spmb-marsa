<?php

namespace Database\Seeders;

use App\Models\PpdbPeriod;
use App\Models\SpecialProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class SpecialProgramSeeder extends Seeder
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
            'Kelas Khusus Olahraga (KKO)',
            'Pondok Pesantren',
        ];

        foreach ($items as $index => $name) {
            $specialProgram = SpecialProgram::updateOrCreate(
                [
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );

            $period->specialPrograms()->syncWithoutDetaching([
                $specialProgram->id => [
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            ]);
        }
    }
}