<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>
        Kartu Pendaftaran {{ $registration->registration_number }}
    </title>

    <style>
        @page {
            margin: 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 12px;
        }

        .page {
            padding: 22px;
        }

        .card {
            width: 100%;
            border: 2px solid #0f172a;
            border-radius: 10px;
            overflow: hidden;
        }

        .header {
            padding: 18px 22px;
            border-bottom: 2px solid #0f172a;
            text-align: center;
        }

        .school {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .title {
            margin: 5px 0 0;
            font-size: 14px;
            font-weight: bold;
        }

        .period {
            margin-top: 4px;
            color: #475569;
        }

        .number {
            margin: 18px 22px 0;
            padding: 14px;
            border: 1px solid #94a3b8;
            text-align: center;
        }

        .number-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
        }

        .number-value {
            margin-top: 5px;
            font-size: 18px;
            font-weight: bold;
        }

        .content {
            padding: 18px 22px 22px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 7px 4px;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }

        .label {
            width: 34%;
            color: #64748b;
        }

        .separator {
            width: 3%;
        }

        .value {
            font-weight: bold;
        }

        .notice {
            margin-top: 18px;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
            line-height: 1.5;
            color: #475569;
        }

        .status-access {
            margin-top: 16px;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
        }

        .status-access-table {
            width: 100%;
            border-collapse: collapse;
        }

        .status-access-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }

        .status-qr {
            width: 88px;
            text-align: center;
        }

        .status-qr img {
            width: 82px;
            height: 82px;
        }

        .status-text {
            padding-left: 14px !important;
        }

        .status-title {
            margin: 0 0 4px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-description {
            margin: 0;
            font-size: 9px;
            line-height: 1.45;
            color: #475569;
        }

        .footer {
            margin-top: 16px;
            text-align: center;
            font-size: 9px;
            color: #64748b;
        }
    </style>
</head>

<body>
<div class="page">

    <div class="card">

        <div class="header">
            <p class="school">
                {{ $registration->period->school->name ?? config('app.name') }}
            </p>

            <p class="title">
                KARTU PENDAFTARAN SPMB
            </p>

            <div class="period">
                Tahun Pelajaran {{ $registration->period->name }}
            </div>
        </div>

        <div class="number">
            <div class="number-label">
                Nomor Pendaftaran
            </div>

            <div class="number-value">
                {{ $registration->registration_number }}
            </div>
        </div>

        <div class="content">

            <table>
                <tr>
                    <td class="label">Nama Lengkap</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $registration->full_name }}
                    </td>
                </tr>

                <tr>
                    <td class="label">NISN</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $registration->nisn ?: '-' }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Jenis Kelamin</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $registration->gender === 'L'
                            ? 'Laki-laki'
                            : ($registration->gender === 'P'
                                ? 'Perempuan'
                                : '-') }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Asal Sekolah</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $registration->origin_school ?: '-' }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Jurusan</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $registration->major->code ?? '-' }}
                        @if ($registration->major?->name)
                            - {{ $registration->major->name }}
                        @endif
                    </td>
                </tr>

                <tr>
                    <td class="label">Jalur Pendaftaran</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $registration->admissionPath->name ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Tanggal Pendaftaran</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $registration->registered_at
                            ? $registration->registered_at
                                ->format('d/m/Y H:i')
                            : '-' }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Periode SPMB</td>
                    <td class="separator">:</td>
                    <td class="value">
                        {{ $registration->period->name }}
                    </td>
                </tr>
            </table>

            @if ($statusQrCode)
                <div class="status-access">
                    <table class="status-access-table">
                        <tr>
                            <td class="status-qr">
                                <img
                                    src="{{ $statusQrCode }}"
                                    alt="QR Cek Status Pendaftaran"
                                >
                            </td>

                            <td class="status-text">
                                <p class="status-title">
                                    CEK STATUS PENDAFTARAN
                                </p>

                                <p class="status-description">
                                    Scan QR Code untuk melihat status
                                    pendaftaran terbaru. Simpan kartu ini
                                    dan jangan membagikan QR Code kepada
                                    orang lain.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            @endif

            <div class="notice">
                Kartu ini merupakan bukti pendaftaran calon siswa.
                Proses selanjutnya adalah wawancara bersama orang tua
                atau wali.
            </div>

        </div>

    </div>

    <div class="footer">
        Dicetak dari {{ config('app.name') }}
    </div>

</div>
</body>
</html>