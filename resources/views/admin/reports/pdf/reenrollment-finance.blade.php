<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>
        Daftar Ulang & Keuangan {{ $period->name }}
    </title>

    <style>
        @page {
            margin: 18mm 12mm 16mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1f2937;
        }

        .header {
            margin-bottom: 16px;
            text-align: center;
        }

        .school-name {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-title {
            margin: 6px 0 0;
            font-size: 13px;
            font-weight: bold;
        }

        .period {
            margin-top: 4px;
            color: #475569;
        }

        .meta {
            width: 100%;
            margin-bottom: 12px;
        }

        .meta td {
            padding: 2px 0;
            border: none;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th {
            background: #e2e8f0;
            font-weight: bold;
            text-align: center;
        }

        table.data th,
        table.data td {
            border: 1px solid #94a3b8;
            padding: 5px 4px;
            vertical-align: top;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .footer {
            margin-top: 12px;
            font-size: 8px;
            color: #64748b;
        }
    </style>
</head>

<body>

    @php
        $grandRequired = 0;
        $grandPaid = 0;
        $grandRemaining = 0;
    @endphp

    <div class="header">

        <p class="school-name">
            {{ $period->school?->name ?? 'SPMB MARSA' }}
        </p>

        <p class="report-title">
            DAFTAR ULANG & KEUANGAN
        </p>

        <div class="period">
            Periode {{ $period->name }}
        </div>

    </div>

    <table class="meta">

        <tr>
            <td>
                Total Peserta:
                <strong>
                    {{ $registrations->count() }}
                </strong>
            </td>

            <td style="text-align: right;">
                Dicetak:
                {{ now()->format('d/m/Y H:i') }}
            </td>
        </tr>

    </table>

    <table class="data">

        <thead>

            <tr>

                <th style="width: 24px;">
                    No
                </th>

                <th>
                    No. Pendaftaran
                </th>

                <th>
                    Nama Lengkap
                </th>

                <th>
                    Jurusan
                </th>

                <th>
                    Biaya
                </th>

                <th>
                    Dibayar
                </th>

                <th>
                    Sisa
                </th>

                <th>
                    Status
                </th>

                <th>
                    Pembayaran Terakhir
                </th>

            </tr>

        </thead>

        <tbody>

            @forelse ($registrations as $registration)

                @php
                    $requiredFee = (
                        $registration->wave
                        && $registration->wave->reenroll_fee !== null
                    )
                        ? (int) $registration->wave->reenroll_fee
                        : (int) (
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

                    $grandRequired += $requiredFee;
                    $grandPaid += $totalPaid;
                    $grandRemaining += $remaining;
                @endphp

                <tr>

                    <td class="center">
                        {{ $loop->iteration }}
                    </td>

                    <td class="nowrap">
                        {{ $registration->registration_number }}
                    </td>

                    <td>
                        {{ $registration->full_name }}
                    </td>

                    <td class="center">
                        {{ $registration->major?->code ?? '-' }}
                    </td>

                    <td class="right nowrap">
                        Rp {{ number_format(
                            $requiredFee,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td class="right nowrap">
                        Rp {{ number_format(
                            $totalPaid,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td class="right nowrap">
                        Rp {{ number_format(
                            $remaining,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td class="center">
                        {{ $isPaidOff ? 'Lunas' : 'Belum Lunas' }}
                    </td>

                    <td class="center nowrap">
                        {{ $lastPayment?->paid_at?->format('d/m/Y H:i') ?? '-' }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="9"
                        class="center"
                    >
                        Belum ada data daftar ulang pada periode ini.
                    </td>
                </tr>

            @endforelse

        </tbody>

        @if ($registrations->isNotEmpty())

            <tfoot>

                <tr>

                    <td
                        colspan="4"
                        style="font-weight: bold;"
                    >
                        TOTAL
                    </td>

                    <td class="right nowrap" style="font-weight: bold;">
                        Rp {{ number_format(
                            $grandRequired,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td class="right nowrap" style="font-weight: bold;">
                        Rp {{ number_format(
                            $grandPaid,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td class="right nowrap" style="font-weight: bold;">
                        Rp {{ number_format(
                            $grandRemaining,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td colspan="2"></td>

                </tr>

            </tfoot>

        @endif

    </table>

    <div class="footer">
        Dokumen ini dibuat melalui Sistem SPMB MARSA.
    </div>

</body>

</html>