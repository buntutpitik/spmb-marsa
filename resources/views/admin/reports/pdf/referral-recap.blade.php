<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>
        Rekap Referral / Pembawa {{ $period->name }}
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
            vertical-align: middle;
        }

        .center {
            text-align: center;
        }

        .total-row {
            background: #f8fafc;
            font-weight: bold;
        }

        .footer {
            margin-top: 12px;
            font-size: 8px;
            color: #64748b;
        }
    </style>
</head>

<body>

    <div class="header">

        <p class="school-name">
            {{ $period->school?->name ?? 'SPMB MARSA' }}
        </p>

        <p class="report-title">
            REKAP REFERRAL / PEMBAWA
        </p>

        <div class="period">
            Periode {{ $period->name }}
        </div>

    </div>

    <table class="meta">

        <tr>

            <td>
                Total Kelompok Referral:
                <strong>
                    {{ $rows->count() }}
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

                <th style="width: 28px;">
                    No
                </th>

                <th>
                    Nama Pembawa
                </th>

                <th>
                    Sumber Referral
                </th>

                <th>
                    Total
                </th>

                <th>
                    Terdaftar
                </th>

                <th>
                    Diterima
                </th>

                <th>
                    Ditolak
                </th>

                <th>
                    Daftar Ulang
                </th>

                <th>
                    Mengundurkan Diri
                </th>

            </tr>

        </thead>

        <tbody>

            @forelse ($rows as $row)

                <tr>

                    <td class="center">
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        {{ $row->referrer_name_label }}
                    </td>

                    <td>
                        {{ $row->referrer_source_label }}
                    </td>

                    <td class="center">
                        {{ (int) $row->total }}
                    </td>

                    <td class="center">
                        {{ (int) $row->registered }}
                    </td>

                    <td class="center">
                        {{ (int) $row->accepted }}
                    </td>

                    <td class="center">
                        {{ (int) $row->rejected }}
                    </td>

                    <td class="center">
                        {{ (int) $row->reenrolled }}
                    </td>

                    <td class="center">
                        {{ (int) $row->withdrawn }}
                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="9"
                        class="center"
                    >
                        Belum ada data referral pada periode ini.
                    </td>

                </tr>

            @endforelse

        </tbody>

        @if ($rows->isNotEmpty())

            <tfoot>

                <tr class="total-row">

                    <td></td>

                    <td colspan="2">
                        TOTAL
                    </td>

                    <td class="center">
                        {{ $totals['total'] }}
                    </td>

                    <td class="center">
                        {{ $totals['registered'] }}
                    </td>

                    <td class="center">
                        {{ $totals['accepted'] }}
                    </td>

                    <td class="center">
                        {{ $totals['rejected'] }}
                    </td>

                    <td class="center">
                        {{ $totals['reenrolled'] }}
                    </td>

                    <td class="center">
                        {{ $totals['withdrawn'] }}
                    </td>

                </tr>

            </tfoot>

        @endif

    </table>

    <div class="footer">
        Dokumen ini dibuat melalui Sistem SPMB MARSA.
    </div>

</body>

</html>