<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\StylesComparisonSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class PeriodComparisonRegistrationDayTrendSheet implements
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
            'Hari',
            $this->comparison['period_a']->name,
            $this->comparison['period_b']->name,
            'Selisih',
            'Kumulatif A',
            'Kumulatif B',
            'Delta Kumulatif',
        ]];

        foreach (
            $this->comparison['registration_day_trend']
            as $row
        ) {
            $rows[] = [
                'Hari ke-'.$row['day'],
                $row['count_a'],
                $row['count_b'],
                $row['delta'],
                $row['cumulative_a'],
                $row['cumulative_b'],
                $row['cumulative_delta'],
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Tren Hari Pendaftaran';
    }
}