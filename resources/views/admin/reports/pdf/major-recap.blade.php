<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>
        Rekap Jurusan {{ $period->name }}
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
            border: none;
            padding: 2px 0;
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
            REKAP JURUSAN
        </p>

        <div class="period">
            Periode {{ $period->name }}
        </div>

    </div>

    <table class="meta">
        <tr>
            <td>
                Total Jurusan:
                <strong>{{ $rows->count() }}</strong>
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
                <th>No</th>
                <th>Kode</th>
                <th>Jurusan</th>
                <th>L</th>
                <th>P</th>
                <th>Total</th>
                <th>Terdaftar</th>
                <th>Diterima</th>
                <th>Ditolak</th>
                <th>Daftar Ulang</th>
                <th>Mengundurkan Diri</th>
            </tr>
        </thead>

        <tbody>

            @forelse ($rows as $row)

                <tr>

                    <td class="center">
                        {{ $loop->iteration }}
                    </td>

                    <td class="center">
                        {{ $row->code }}
                    </td>

                    <td>
                        {{ $row->name }}
                    </td>

                    <td class="center">
                        {{ (int) $row->male }}
                    </td>

                    <td class="center">
                        {{ (int) $row->female }}
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
                        colspan="11"
                        class="center"
                    >
                        Belum ada data jurusan pada periode ini.
                    </td>
                </tr>

            @endforelse

        </tbody>

        @if ($rows->isNotEmpty())

            <tfoot>

                <tr class="total-row">

                    <td colspan="3">
                        TOTAL
                    </td>

                    <td class="center">
                        {{ $totals['male'] }}
                    </td>

                    <td class="center">
                        {{ $totals['female'] }}
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