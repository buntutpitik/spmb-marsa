<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\StylesComparisonSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class PeriodComparisonSummarySheet implements
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
        $sheet->getStyle('C5')
            ->getNumberFormat()
            ->setFormatCode('0.0"%"');
    }

    public function __construct(
        private readonly array $comparison
    ) {
    }

    protected function comparisonHeaderRow(): int
    {
        return 3;
    }

    public function array(): array
    {
        $periodA = $this->comparison['period_a'];
        $periodB = $this->comparison['period_b'];
        $growth = $this->comparison['growth'];

        return [
            [
                'Perbandingan Antar Tahun SPMB MARSA',
            ],
            [],
            [
                'Metrik',
                $periodA->name,
                $periodB->name,
                'Selisih',
            ],
            [
                'Total Pendaftar',
                $this->comparison['total_a'],
                $this->comparison['total_b'],
                $this->comparison['delta'],
            ],
            [
                'Pertumbuhan',
                null,
                $growth,
                null,
            ],
        ];
    }

    public function title(): string
    {
        return 'Ringkasan';
    }
}