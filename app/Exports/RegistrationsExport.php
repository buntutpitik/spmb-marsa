<?php

namespace App\Exports;

use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RegistrationsExport implements
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
                'period',
                'wave',
                'major',
                'admissionPath',
                'creator',
                'reliefOptions',
                'specialPrograms',
            ])
            ->where(
                'period_id',
                $this->period->id
            )
            ->orderBy('registered_at')
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No. Pendaftaran',
            'Periode',
            'Gelombang',
            'Jalur Pendaftaran',
            'Jurusan',

            'NIK',
            'NISN',
            'Nama Lengkap',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Agama',

            'Asal Sekolah',

            'Dusun',
            'RT',
            'RW',
            'Desa / Kelurahan',
            'Kecamatan',
            'Kabupaten / Kota',
            'Provinsi',
            'Kode Pos',

            'Nama Ayah',
            'Pekerjaan Ayah',
            'Nama Ibu',
            'Pekerjaan Ibu',

            'No. WhatsApp',

            'Nilai Kelulusan',
            'Keringanan Prestasi',
            'Pilihan Keringanan',
            'Program Khusus',

            'Nama Pembawa',
            'Sumber Referral',

            'Sumber Data',
            'Status',

            'Petugas Input',

            'Tanggal Pendaftaran',
            'Tanggal Diterima',
            'Tanggal Ditolak',
            'Tanggal Daftar Ulang',
            'Tanggal Mengundurkan Diri',

            'Catatan',
        ];
    }

    public function map($registration): array
    {
        return [
            $registration->registration_number,

            $registration->period
                ?->name,

            $registration->wave
                ?->name,

            $registration->admissionPath
                ?->name,

            $registration->major
                ?->name,

            $registration->nik,

            $registration->nisn,

            $registration->full_name,

            $registration->birth_place,

            $registration->birth_date
                ?->format('d/m/Y'),

            match ($registration->gender) {
                'L' => 'Laki-laki',
                'P' => 'Perempuan',
                default => $registration->gender,
            },

            $registration->religion,

            $registration->origin_school,

            $registration->hamlet,

            $registration->rt,

            $registration->rw,

            $registration->village,

            $registration->district,

            $registration->city,

            $registration->province,

            $registration->postal_code,

            $registration->father_name,

            $registration->father_job,

            $registration->mother_name,

            $registration->mother_job,

            $registration->whatsapp,

            $registration->graduation_score,

            $registration->achievement_relief,

            $registration->reliefOptions
                ->pluck('name')
                ->implode(', '),

            $registration->specialPrograms
                ->pluck('name')
                ->implode(', '),

            $registration->referrer_name,

            $registration->referrer_source,

            match ($registration->data_source) {
                'PUBLIC' => 'Publik',
                'ADMIN' => 'Admin',
                default => $registration->data_source,
            },

            match ($registration->status) {
                'REGISTERED' => 'Terdaftar',
                'ACCEPTED' => 'Diterima',
                'REJECTED' => 'Ditolak',
                'REENROLLED' => 'Daftar Ulang',
                'WITHDRAWN' => 'Mengundurkan Diri',
                default => $registration->status,
            },

            $registration->creator
                ?->name,

            $registration->registered_at
                ?->format('d/m/Y H:i:s'),

            $registration->accepted_at
                ?->format('d/m/Y H:i:s'),

            $registration->rejected_at
                ?->format('d/m/Y H:i:s'),

            $registration->reenrolled_at
                ?->format('d/m/Y H:i:s'),

            $registration->withdrawn_at
                ?->format('d/m/Y H:i:s'),

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
                $sheet = $event->sheet->getDelegate();

                $highestColumn =
                    $sheet->getHighestColumn();

                $highestRow =
                    $sheet->getHighestRow();

                /*
                * Freeze header.
                */
                $sheet->freezePane('A2');

                /*
                * Autofilter.
                */
                $sheet
                    ->setAutoFilter(
                        "A1:{$highestColumn}{$highestRow}"
                    );

                /*
                * Tinggi header.
                */
                $sheet
                    ->getRowDimension(1)
                    ->setRowHeight(24);

                /*
                * Vertical alignment seluruh data.
                */
                $sheet
                    ->getStyle(
                        "A1:{$highestColumn}{$highestRow}"
                    )
                    ->getAlignment()
                    ->setVertical('center');

                /*
                * Wrap text supaya kolom panjang
                * seperti alamat/catatan tetap rapi.
                */
                $sheet
                    ->getStyle(
                        "A1:{$highestColumn}{$highestRow}"
                    )
                    ->getAlignment()
                    ->setWrapText(true);
            },
        ];
    }

    public function title(): string
    {
        return 'Data Pendaftar';
    }
}