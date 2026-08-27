<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\StylesComparisonSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class PeriodComparisonTrendSheet implements
    FromArray,
    WithTitle,
    WithEvents,
    ShouldAutoSize
{
    use StylesComparisonSheet;

    public function __construct(
        private readonly array $comparison
    ) {
    }

    public function array(): array
    {
        $rows = [[
            'Bulan',
            $this->comparison['period_a']->name,
            $this->comparison['period_b']->name,
            'Selisih',
        ]];

        foreach (
            $this->comparison['monthly_registration_trend']
            as $row
        ) {
            $rows[] = [
                $row['label'],
                $row['count_a'],
                $row['count_b'],
                $row['delta'],
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Tren Pendaftaran';
    }
}