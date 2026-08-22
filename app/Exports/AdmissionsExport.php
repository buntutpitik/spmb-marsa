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

class AdmissionsExport implements
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
        return Registration::query()
            ->with([
                'major',
                'admissionPath',
                'creator',
            ])
            ->where(
                'period_id',
                $this->period->id
            )
            ->whereIn('status', [
                'ACCEPTED',
                'REJECTED',
                'WITHDRAWN',
            ])
            ->orderBy('full_name')
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No. Pendaftaran',
            'Nama Lengkap',
            'NIK',
            'NISN',
            'Asal Sekolah',
            'Jurusan',
            'Jalur',
            'Status',
            'Tanggal Daftar',
            'Tanggal Diterima',
            'Tanggal Ditolak',
            'Tanggal Mengundurkan Diri',
            'Petugas Input',
            'Catatan',
        ];
    }

    public function map($registration): array
    {
        return [
            $registration->registration_number,
            $registration->full_name,
            $registration->nik,
            $registration->nisn,
            $registration->origin_school,
            $registration->major?->name,
            $registration->admissionPath?->name,

            match ($registration->status) {                
                'ACCEPTED' => 'Diterima',
                'REJECTED' => 'Ditolak',
                'WITHDRAWN' => 'Mengundurkan Diri',
                default => $registration->status,
            },

            $registration->registered_at
                ?->format('d/m/Y H:i:s'),

            $registration->accepted_at
                ?->format('d/m/Y H:i:s'),

            $registration->rejected_at
                ?->format('d/m/Y H:i:s'),

            $registration->withdrawn_at
                ?->format('d/m/Y H:i:s'),

            $registration->creator?->name,
            $registration->notes,
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
            },
        ];
    }

    public function title(): string
    {
        return 'Laporan Penerimaan';
    }
}