<?php

namespace App\Exports\Sheets\Concerns;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

trait StylesComparisonSheet
{
    protected function comparisonHeaderRow(): int
    {
        return 1;
    }

    protected function applyComparisonNumberFormats(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $highestRow
    ): void {
        //
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $headerRow = $this->comparisonHeaderRow();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                $headerRange =
                    'A'.$headerRow.':'.$highestColumn.$headerRow;

                $dataRange =
                    'A'.$headerRow.':'.$highestColumn.$highestRow;

                $sheet->freezePane(
                    'A'.($headerRow + 1)
                );

                $sheet->setAutoFilter($headerRange);

                $sheet->getStyle($headerRange)
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle($headerRange)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFE2E8F0');

                $sheet->getStyle($headerRange)
                    ->getAlignment()
                    ->setVertical(
                        Alignment::VERTICAL_CENTER
                    );

                $sheet->getStyle($dataRange)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(
                        Border::BORDER_THIN
                    );

                $sheet->getStyle($dataRange)
                    ->getAlignment()
                    ->setVertical(
                        Alignment::VERTICAL_CENTER
                    );

                $sheet->getRowDimension($headerRow)
                    ->setRowHeight(24);

                $this->applyComparisonNumberFormats(
                    $sheet,
                    $highestRow
                );
            },
        ];
    }
}