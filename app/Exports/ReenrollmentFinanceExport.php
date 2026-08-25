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

class ReenrollmentFinanceExport implements
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
                'major',
                'reenrollmentPayments.receiver',
            ])
            ->where(
                'period_id',
                $this->period->id
            )
            ->whereIn('status', [
                'ACCEPTED',
                'REENROLLED',
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
            'Jurusan',
            'Status Pendaftaran',

            'Biaya Daftar Ulang',
            'Total Dibayar',
            'Sisa Tagihan',
            'Status Pembayaran',

            'Jumlah Transaksi',
            'Tanggal Pembayaran Terakhir',
            'Metode Pembayaran Terakhir',
            'Nomor Referensi Terakhir',
            'Petugas Penerima Terakhir',

            'Tanggal Diterima',
            'Tanggal Daftar Ulang',

            'Catatan Pendaftar',
        ];
    }

    public function map($registration): array
    {
        $requiredFee = (int) (
            $registration->period?->default_reenroll_fee
            ?? 0
        );

        $payments = $registration
            ->reenrollmentPayments
            ->sortBy('paid_at');

        $totalPaid = (int) $payments
            ->sum('amount');

        $remaining = max(
            0,
            $requiredFee - $totalPaid
        );

        $isPaidOff =
            $requiredFee > 0
            && $remaining === 0;

        $lastPayment = $payments
            ->sortByDesc('paid_at')
            ->first();

        return [
            $registration->registration_number,
            $registration->full_name,
            $registration->major?->name,

            match ($registration->status) {
                'ACCEPTED' => 'Diterima',
                'REENROLLED' => 'Daftar Ulang',
                default => $registration->status,
            },

            $requiredFee,
            $totalPaid,
            $remaining,

            $isPaidOff
                ? 'Lunas'
                : 'Belum Lunas',

            $payments->count(),

            $lastPayment?->paid_at
                ?->format('d/m/Y H:i:s'),

            $lastPayment?->payment_method,

            $lastPayment?->reference_number,

            $lastPayment?->receiver
                ?->name,

            $registration->accepted_at
                ?->format('d/m/Y H:i:s'),

            $registration->reenrolled_at
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

                /*
                 * Format nominal sebagai angka
                 * dengan pemisah ribuan.
                 *
                 * Kolom:
                 * E = Biaya
                 * F = Dibayar
                 * G = Sisa
                 */
                if ($highestRow >= 2) {
                    $sheet
                        ->getStyle("E2:G{$highestRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Daftar Ulang & Keuangan';
    }
}