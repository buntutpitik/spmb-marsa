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

class ReferralRecapExport implements
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
            ->where(function ($query) {
                $query
                    ->where(function ($subQuery) {
                        $subQuery
                            ->whereNotNull('referrer_name')
                            ->whereRaw(
                                "TRIM(referrer_name) <> ''"
                            );
                    })
                    ->orWhere(function ($subQuery) {
                        $subQuery
                            ->whereNotNull('referrer_source')
                            ->whereRaw(
                                "TRIM(referrer_source) <> ''"
                            );
                    });
            })
            ->select([
                'referrer_name',
                'referrer_source',
            ])
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
            ->groupBy(
                'referrer_name',
                'referrer_source'
            )
            ->orderByDesc('total')
            ->orderBy('referrer_name')
            ->orderBy('referrer_source')
            ->get()
            ->map(function ($row) {
                $row->referrer_name_label =
                    trim(
                        (string) $row->referrer_name
                    ) !== ''
                        ? trim(
                            (string) $row->referrer_name
                        )
                        : '-';

                $row->referrer_source_label =
                    trim(
                        (string) $row->referrer_source
                    ) !== ''
                        ? trim(
                            (string) $row->referrer_source
                        )
                        : '-';

                return $row;
            });

        $totalRow = (object) [
            'referrer_name_label' => 'TOTAL',
            'referrer_source_label' => '',
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
            'Nama Pembawa',
            'Sumber Referral',
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
            $row->referrer_name_label,
            $row->referrer_source_label,
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
                            "C2:H{$highestRow}"
                        )
                        ->getAlignment()
                        ->setHorizontal('center');
                }

                if ($highestRow >= 2) {
                    $sheet
                        ->getStyle(
                            "A{$highestRow}:H{$highestRow}"
                        )
                        ->getFont()
                        ->setBold(true);
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Referral Pembawa';
    }
}