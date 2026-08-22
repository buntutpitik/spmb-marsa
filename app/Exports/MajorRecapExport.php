<?php

namespace App\Exports;

use App\Models\PpdbPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MajorRecapExport implements
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
        $rows = DB::table('period_majors')
            ->join(
                'majors',
                'majors.id',
                '=',
                'period_majors.major_id'
            )
            ->leftJoin('registrations', function ($join) {
                $join
                    ->on(
                        'registrations.major_id',
                        '=',
                        'majors.id'
                    )
                    ->where(
                        'registrations.period_id',
                        '=',
                        $this->period->id
                    );
            })
            ->where(
                'period_majors.period_id',
                $this->period->id
            )
            ->where(
                'period_majors.is_active',
                true
            )
            ->select([
                'majors.code',
                'majors.name',
                'majors.sort_order',
            ])
            ->selectRaw(
                "SUM(CASE WHEN registrations.gender = 'L' THEN 1 ELSE 0 END) as male"
            )
            ->selectRaw(
                "SUM(CASE WHEN registrations.gender = 'P' THEN 1 ELSE 0 END) as female"
            )
            ->selectRaw(
                'COUNT(registrations.id) as total'
            )
            ->selectRaw(
                "SUM(CASE WHEN registrations.status = 'REGISTERED' THEN 1 ELSE 0 END) as registered"
            )
            ->selectRaw(
                "SUM(CASE WHEN registrations.status = 'ACCEPTED' THEN 1 ELSE 0 END) as accepted"
            )
            ->selectRaw(
                "SUM(CASE WHEN registrations.status = 'REJECTED' THEN 1 ELSE 0 END) as rejected"
            )
            ->selectRaw(
                "SUM(CASE WHEN registrations.status = 'REENROLLED' THEN 1 ELSE 0 END) as reenrolled"
            )
            ->selectRaw(
                "SUM(CASE WHEN registrations.status = 'WITHDRAWN' THEN 1 ELSE 0 END) as withdrawn"
            )
            ->groupBy(
                'majors.code',
                'majors.name',
                'majors.sort_order'
            )
            ->orderBy('majors.sort_order')
            ->orderBy('majors.name')
            ->get();

        $totalRow = (object) [
            'code' => 'TOTAL',
            'name' => '',
            'male' => (int) $rows->sum('male'),
            'female' => (int) $rows->sum('female'),
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
            'Kode Jurusan',
            'Nama Jurusan',
            'Laki-laki',
            'Perempuan',
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
            $row->code,
            $row->name,
            (int) $row->male,
            (int) $row->female,
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
                            "C2:J{$highestRow}"
                        )
                        ->getAlignment()
                        ->setHorizontal('center');
                }

                if ($highestRow >= 2) {
                    $sheet
                        ->getStyle(
                            "A{$highestRow}:J{$highestRow}"
                        )
                        ->getFont()
                        ->setBold(true);
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Rekap Jurusan';
    }
}