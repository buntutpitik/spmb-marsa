<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\StylesComparisonSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class PeriodComparisonFinanceSheet implements
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
        $sheet->getStyle('B4:D4')
            ->getNumberFormat()
            ->setFormatCode('"Rp"#,##0');
    }

    public function __construct(
        private readonly array $comparison
    ) {
    }

    public function array(): array
    {
        $finance = $this->comparison['reenrollment_finance'];

        return [
            [
                'Metrik',
                $this->comparison['period_a']->name,
                $this->comparison['period_b']->name,
                'Selisih',
            ],
            [
                'Jumlah Daftar Ulang',
                $finance['reenrolled_a'],
                $finance['reenrolled_b'],
                $finance['reenrolled_delta'],
            ],
            [
                'Jumlah Transaksi',
                $finance['transactions_a'],
                $finance['transactions_b'],
                $finance['transactions_delta'],
            ],
            [
                'Total Pembayaran',
                $finance['payment_a'],
                $finance['payment_b'],
                $finance['payment_delta'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Daftar Ulang & Keuangan';
    }
}