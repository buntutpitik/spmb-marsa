<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>Bukti Pembayaran Daftar Ulang</title>

    <style>
        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            line-height: 1.45;
            color: #0f172a;
        }

        .page {
            padding: 34px 42px 28px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .school-name {
            font-size: 17px;
            font-weight: bold;
            line-height: 1.25;
            margin-bottom: 3px;
        }

        .school-info {
            color: #475569;
            font-size: 9.5px;
            line-height: 1.4;
        }

        .title {
            text-align: center;
            margin: 16px 0 18px;
        }

        .title h1 {
            margin: 0;
            font-size: 17px;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .title p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 9.5px;
        }

        .section {
            page-break-inside: avoid;
        }

        .section-title {
            margin-top: 13px;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .identity td {
            padding: 3px 0;
            vertical-align: top;
        }

        .identity .label {
            width: 29%;
            color: #64748b;
        }

        .identity .separator {
            width: 3%;
            text-align: center;
        }

        .identity .value {
            width: 68%;
        }

        .payment-table {
            margin-top: 8px;
            page-break-inside: avoid;
        }

        .payment-table th,
        .payment-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 9px;
        }

        .payment-table th {
            background: #f1f5f9;
            text-align: left;
            font-size: 9.5px;
        }

        .amount {
            width: 34%;
            text-align: right !important;
            white-space: nowrap;
        }

        .summary-wrap {
            margin-top: 9px;
            page-break-inside: avoid;
        }

        .summary-spacer {
            width: 34%;
        }

        .summary-cell {
            width: 66%;
        }

        .summary td {
            padding: 3px 0 3px 8px;
        }

        .summary .label {
            width: 62%;
            text-align: right;
            color: #475569;
        }

        .summary .value {
            width: 38%;
            text-align: right;
            font-weight: bold;
            white-space: nowrap;
        }

        .summary .remaining {
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
        }

        .status-row {
            text-align: right;
            margin-top: 8px;
            page-break-inside: avoid;
        }

        .paid {
            display: inline-block;
            padding: 3px 9px;
            border: 1px solid #16a34a;
            border-radius: 4px;
            color: #166534;
            font-size: 9.5px;
            font-weight: bold;
        }

        .notes {
            page-break-inside: avoid;
        }

        .notes-text {
            min-height: 16px;
        }

        .footer-table {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .footer-table td {
            width: 50%;
            vertical-align: top;
        }

        .signature {
            text-align: center;
        }

        .signature-space {
            height: 54px;
        }

        .receiver {
            font-weight: bold;
            text-decoration: underline;
        }

        .note {
            margin-top: 18px;
            padding-top: 7px;
            border-top: 1px dashed #cbd5e1;
            color: #64748b;
            font-size: 8px;
            line-height: 1.4;
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    @php
        $school = $registration->period?->school;

        $paymentMethod = match (
            strtoupper((string) $payment->payment_method)
        ) {
            'CASH' => 'Tunai',
            'TRANSFER' => 'Transfer',
            default => $payment->payment_method ?: '-',
        };

        $receiptNumber = sprintf(
            'DU-%d-%06d',
            (int) $registration->period?->year_start,
            (int) $payment->id
        );
    @endphp

    <div class="page">
        <div class="header">
            <div class="school-name">
                {{ $school?->name ?? 'SPMB MARSA' }}
            </div>

            <div class="school-info">
                @if ($school?->address)
                    {{ $school->address }}
                @endif

                @if ($school?->phone)
                    | Telp. {{ $school->phone }}
                @endif

                @if ($school?->email)
                    | {{ $school->email }}
                @endif
            </div>
        </div>

        <div class="title">
            <h1>Bukti Pembayaran Daftar Ulang</h1>

            <p>
                Periode {{ $registration->period?->name }}
            </p>
        </div>

        <div class="section">
            <div class="section-title">
                Data Pendaftar
            </div>

            <table class="identity">
                <tr>
                    <td class="label">Nomor Pendaftaran</td>
                    <td class="separator">:</td>
                    <td class="value">
                        <strong>
                            {{ $registration->registration_number }}
                        </strong>
                    </td>
                </tr>

                <tr>
                    <td class="label">Nama</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $registration->full_name }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Jurusan</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $registration->major?->name ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Jalur Pendaftaran</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $registration->admissionPath?->name ?? '-' }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">
                Detail Transaksi
            </div>

            <table class="identity">
                <tr>
                    <td class="label">Nomor Bukti</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $receiptNumber }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Tanggal Pembayaran</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $payment->paid_at
                            ?->timezone(config('app.timezone'))
                            ->format('d/m/Y H:i') }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Metode Pembayaran</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $paymentMethod }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Nomor Referensi</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $payment->reference_number ?: '-' }}
                    </td>
                </tr>
            </table>
        </div>

        <table class="payment-table">
            <thead>
                <tr>
                    <th>Keterangan</th>
                    <th class="amount">Nominal</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>Pembayaran Daftar Ulang</td>
                    <td class="amount">
                        Rp {{ number_format(
                            $payment->amount,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="summary-wrap">
            <tr>
                <td class="summary-spacer"></td>

                <td class="summary-cell">
                    <table class="summary">
                        <tr>
                            <td class="label">
                                Biaya Daftar Ulang
                            </td>
                            <td class="value">
                                Rp {{ number_format(
                                    $requiredFee,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                Pembayaran Transaksi Ini
                            </td>
                            <td class="value">
                                Rp {{ number_format(
                                    $payment->amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                Total Dibayar s.d. Transaksi Ini
                            </td>
                            <td class="value">
                                Rp {{ number_format(
                                    $totalPaidAtTransaction,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label remaining">
                                Sisa Tagihan
                            </td>
                            <td class="value remaining">
                                Rp {{ number_format(
                                    $remainingAtTransaction,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        @if ($remainingAtTransaction === 0)
            <div class="status-row">
                <span class="paid">
                    LUNAS
                </span>
            </div>
        @endif

        @if ($payment->notes)
            <div class="notes">
                <div class="section-title">
                    Catatan
                </div>

                <div class="notes-text">
                    {{ $payment->notes }}
                </div>
            </div>
        @endif

        <table class="footer-table">
            <tr>
                <td></td>

                <td class="signature">
                    <div>Penerima,</div>

                    <div class="signature-space"></div>

                    <div class="receiver">
                        {{ $payment->receiver?->name ?? '-' }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="note">
            Bukti pembayaran ini diterbitkan oleh sistem SPMB
            dan mencatat posisi pembayaran pada saat transaksi
            tersebut dilakukan.
        </div>
    </div>
</body>
</html>
