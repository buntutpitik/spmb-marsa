<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>
        Laporan Penerimaan {{ $period->name }}
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

    <div class="header">

        <p class="school-name">
            {{ $period->school?->name ?? 'SPMB MARSA' }}
        </p>

        <p class="report-title">
            LAPORAN PENERIMAAN
        </p>

        <div class="period">
            Periode {{ $period->name }}
        </div>

    </div>

    <table class="meta">

        <tr>
            <td>
                Total Data:
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
                    Asal Sekolah
                </th>

                <th>
                    Jurusan
                </th>

                <th>
                    Jalur
                </th>

                <th>
                    Status
                </th>

                <th>
                    Tanggal Status
                </th>

            </tr>

        </thead>

        <tbody>

            @forelse ($registrations as $registration)

                @php
                    $statusLabel = match ($registration->status) {
                        'ACCEPTED' => 'Diterima',
                        'REJECTED' => 'Ditolak',
                        'WITHDRAWN' => 'Mengundurkan Diri',
                        default => $registration->status,
                    };

                    $statusDate = match ($registration->status) {
                        'ACCEPTED' => $registration->accepted_at,
                        'REJECTED' => $registration->rejected_at,
                        'WITHDRAWN' => $registration->withdrawn_at,
                        default => null,
                    };
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

                    <td>
                        {{ $registration->origin_school ?: '-' }}
                    </td>

                    <td>
                        {{ $registration->major?->code ?? '-' }}
                    </td>

                    <td>
                        {{ $registration->admissionPath?->name ?? '-' }}
                    </td>

                    <td>
                        {{ $statusLabel }}
                    </td>

                    <td class="nowrap">
                        {{ $statusDate?->format('d/m/Y H:i') ?? '-' }}
                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="8"
                        class="center"
                    >
                        Belum ada data hasil penerimaan pada periode ini.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

    <div class="footer">
        Dokumen ini dibuat melalui Sistem SPMB MARSA.
    </div>

</body>

</html>