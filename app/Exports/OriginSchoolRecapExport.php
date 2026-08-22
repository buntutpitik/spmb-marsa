<?php

namespace App\Exports;

use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OriginSchoolRecapExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithTitle,
    WithStyles,
    WithEvents
{
    public function __construct(
        protected PpdbPeriod $period
    ) {
    }

    public function collection(): Collection
    {
        $rows = Registration::query()
            ->where(
                'period_id',
                $this->period->id
            )
            ->whereNotNull('origin_school')
            ->whereRaw("TRIM(origin_school) <> ''")
            ->select('origin_school')
            ->selectRaw(
                'COUNT(*) as total'
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status = 'REGISTERED'
                        THEN 1
                        ELSE 0
                    END
                ) as registered"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status = 'ACCEPTED'
                        THEN 1
                        ELSE 0
                    END
                ) as accepted"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status = 'REJECTED'
                        THEN 1
                        ELSE 0
                    END
                ) as rejected"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status = 'REENROLLED'
                        THEN 1
                        ELSE 0
                    END
                ) as reenrolled"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status = 'WITHDRAWN'
                        THEN 1
                        ELSE 0
                    END
                ) as withdrawn"
            )
            ->groupBy('origin_school')
            ->orderByDesc('total')
            ->orderBy('origin_school')
            ->get();

        $totalRow = (object) [
            'origin_school' => 'TOTAL',
            'total' => (int) $rows->sum('total'),
            'registered' => (int) $rows->sum('registered'),
            'accepted' => (int) $rows->sum('accepted'),
            'rejected' => (int) $rows->sum('rejected'),
            'reenrolled' => (int) $rows->sum('reenrolled'),
            'withdrawn' => (int) $rows->sum('withdrawn'),
        ];

        return $rows->push($totalRow);
    }

    public function headings(): array
    {
        return [
            'Asal Sekolah',
            'Total',
            'Terdaftar',
            'Diterima',
            'Ditolak',
            'Daftar Ulang',
            'Mengundurkan Diri',
        ];
    }

    public function map($row): array
    {
        return [
            $row->origin_school,
            (int) $row->total,
            (int) $row->registered,
            (int) $row->accepted,
            (int) $row->rejected,
            (int) $row->reenrolled,
            (int) $row->withdrawn,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],

                'alignment' => [
                    'vertical' => 'center',
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event
                    ->sheet
                    ->getDelegate();

                $highestColumn =
                    $sheet->getHighestColumn();

                $highestRow =
                    $sheet->getHighestRow();

                $sheet->freezePane('A2');

                $sheet->setAutoFilter(
                    "A1:{$highestColumn}{$highestRow}"
                );

                $sheet
                    ->getRowDimension(1)
                    ->setRowHeight(24);

                $sheet
                    ->getStyle(
                        "A1:{$highestColumn}{$highestRow}"
                    )
                    ->getAlignment()
                    ->setVertical('center')
                    ->setWrapText(true);

                if ($highestRow >= 2) {
                    $sheet
                        ->getStyle(
                            "B2:G{$highestRow}"
                        )
                        ->getAlignment()
                        ->setHorizontal('center');
                }

                if ($highestRow >= 2) {
                    $sheet
                        ->getStyle(
                            "A{$highestRow}:G{$highestRow}"
                        )
                        ->getFont()
                        ->setBold(true);
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Rekap Asal Sekolah';
    }
}