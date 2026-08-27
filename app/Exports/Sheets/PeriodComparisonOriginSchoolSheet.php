<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\StylesComparisonSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class PeriodComparisonOriginSchoolSheet implements
    FromArray,
    WithTitle,
    WithEvents,
    ShouldAutoSize
{
    use StylesComparisonSheet;

    protected function applyComparisonNumberFormats(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $highestRow
    ): void {
        if ($highestRow < 2) {
            return;
        }

        $sheet->getStyle("E2:F{$highestRow}")
            ->getNumberFormat()
            ->setFormatCode('0.0');

        $sheet->getStyle("G2:G{$highestRow}")
            ->getNumberFormat()
            ->setFormatCode('0.0 "pp"');
    }

    public function __construct(
        private readonly array $comparison
    ) {
    }

    public function array(): array
    {
        $rows = [[
            'Sekolah Asal',
            $this->comparison['period_a']->name,
            $this->comparison['period_b']->name,
            'Selisih',
            'Share A',
            'Share B',
            'Delta Share (pp)',
        ]];

        foreach (
            $this->comparison['origin_school_breakdown']
            as $row
        ) {
            $rows[] = [
                $row['name'],
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
        return 'Sekolah Asal';
    }
}