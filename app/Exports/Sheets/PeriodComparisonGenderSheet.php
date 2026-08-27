<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\StylesComparisonSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class PeriodComparisonGenderSheet implements
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
            'Gender',
            $this->comparison['period_a']->name,
            $this->comparison['period_b']->name,
            'Selisih',
            'Share A',
            'Share B',
            'Delta Share (pp)',
        ]];

        foreach (
            $this->comparison['gender_breakdown']
            as $row
        ) {
            $rows[] = [
                $row['label'],
                $row['count_a'],
                $row['count_b'],
                $row['delta'],
                $row['share_a'],
                $row['share_b'],
                $row['share_delta'],
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Gender';
    }
}