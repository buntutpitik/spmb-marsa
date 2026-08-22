@extends('layouts.app')

@section('content')
    <div class="space-y-8">

        {{-- Page Heading --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

            <div>
                <p class="text-sm font-semibold text-emerald-600">
                    Selamat datang
                </p>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Dashboard SPMB MARSA
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Pantau aktivitas pendaftaran, penerimaan, dan daftar ulang
                    dari satu tempat.
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                    Periode Aktif
                </div>

                <div class="mt-0.5 flex items-center gap-2 text-sm font-semibold text-slate-800">

                    @if ($activePeriod)
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        {{ $activePeriod->name }}
                    @else
                        <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                        Belum diatur
                    @endif

                </div>
            </div>

        </div>

        {{-- 4 Main Statistic Cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Total Pendaftar --}}
            <div class="stat-card">

                <div class="flex items-start justify-between gap-4">

                    <div>
                        <div class="stat-label">
                            Total Pendaftar
                        </div>

                        <div class="stat-value">
                            {{ number_format($stats['total']) }}
                        </div>
                    </div>

                    <div class="stat-icon bg-blue-50 text-blue-600">
                        <i
                            data-lucide="users"
                            class="h-5 w-5"
                        ></i>
                    </div>

                </div>

                <div class="mt-5 text-xs text-slate-400">
                    Total calon siswa periode aktif
                </div>

            </div>

            {{-- Diterima --}}
            <div class="stat-card">

                <div class="flex items-start justify-between gap-4">

                    <div>
                        <div class="stat-label">
                            Diterima
                        </div>

                        <div class="stat-value">
                            {{ number_format($stats['accepted']) }}
                        </div>
                    </div>

                    <div class="stat-icon bg-emerald-50 text-emerald-600">
                        <i
                            data-lucide="circle-check-big"
                            class="h-5 w-5"
                        ></i>
                    </div>

                </div>

                <div class="mt-5 text-xs text-slate-400">
                    Calon siswa yang telah diterima
                </div>

            </div>

            {{-- Daftar Ulang --}}
            <div class="stat-card">

                <div class="flex items-start justify-between gap-4">

                    <div>
                        <div class="stat-label">
                            Daftar Ulang
                        </div>

                        <div class="stat-value">
                            {{ number_format($stats['reenrolled']) }}
                        </div>
                    </div>

                    <div class="stat-icon bg-violet-50 text-violet-600">
                        <i
                            data-lucide="wallet-cards"
                            class="h-5 w-5"
                        ></i>
                    </div>

                </div>

                <div class="mt-5 text-xs text-slate-400">
                    Calon siswa yang telah daftar ulang
                </div>

            </div>

            {{-- Mengundurkan Diri --}}
            <div class="stat-card">

                <div class="flex items-start justify-between gap-4">

                    <div>
                        <div class="stat-label">
                            Mengundurkan Diri
                        </div>

                        <div class="stat-value">
                            {{ number_format($stats['withdrawn']) }}
                        </div>
                    </div>

                    <div class="stat-icon bg-amber-50 text-amber-600">
                        <i
                            data-lucide="log-out"
                            class="h-5 w-5"
                        ></i>
                    </div>

                </div>

                <div class="mt-5 text-xs text-slate-400">
                    Calon siswa yang mengundurkan diri
                </div>

            </div>

        </div>

        {{-- Main Dashboard --}}
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.8fr)]">

            {{-- Trend --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h2 class="font-bold text-slate-900">
                            Tren Pendaftaran
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Perkembangan jumlah pendaftar pada periode aktif.
                        </p>
                    </div>

                    <span class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-500">
                        30 Hari
                    </span>

                </div>

                <div class="flex min-h-[340px] items-center justify-center p-6">

                    <div class="max-w-sm text-center">

                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <i
                                data-lucide="chart-no-axes-combined"
                                class="h-7 w-7"
                            ></i>
                        </div>

                        <h3 class="mt-5 font-semibold text-slate-800">
                            Grafik akan tersedia
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Grafik pendaftaran akan ditampilkan setelah modul
                            analitik periode aktif diselesaikan.
                        </p>

                    </div>

                </div>

            </section>

            {{-- System Status --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="font-bold text-slate-900">
                        Status Sistem
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Kondisi layanan utama SPMB MARSA.
                    </p>
                </div>

                <div class="space-y-4 p-6">

                    <div class="system-status">
                        <div>
                            <div class="system-status-label">
                                Aplikasi
                            </div>

                            <div class="system-status-value">
                                Laravel aktif
                            </div>
                        </div>

                        <span class="status-dot bg-emerald-500"></span>
                    </div>

                    <div class="system-status">
                        <div>
                            <div class="system-status-label">
                                Database
                            </div>

                            <div class="system-status-value">
                                MariaDB terhubung
                            </div>
                        </div>

                        <span class="status-dot bg-emerald-500"></span>
                    </div>

                    <div class="system-status">
                        <div>
                            <div class="system-status-label">
                                WhatsApp
                            </div>

                            <div class="system-status-value">
                                {{ config('services.whatsapp.enabled', false)
                                    ? 'Aktif'
                                    : 'Belum diaktifkan' }}
                            </div>
                        </div>

                        <span
                            class="status-dot {{ config('services.whatsapp.enabled', false)
                                ? 'bg-emerald-500'
                                : 'bg-slate-300' }}"
                        ></span>
                    </div>

                    <div class="system-status">

                        <div>
                            <div class="system-status-label">
                                Periode Aktif
                            </div>

                            <div class="system-status-value">
                                {{ $activePeriod?->name ?? 'Belum tersedia' }}
                            </div>
                        </div>

                        <span
                            class="status-dot {{ $activePeriod
                                ? 'bg-emerald-500'
                                : 'bg-amber-400' }}"
                        ></span>

                    </div>

                </div>

            </section>

        </div>

        {{-- Ringkasan Status --}}
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 px-6 py-5">

                <h2 class="font-bold text-slate-900">
                    Ringkasan Status
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan status calon siswa pada periode aktif.
                </p>

            </div>

            <div class="grid divide-y divide-slate-100 sm:grid-cols-5 sm:divide-x sm:divide-y-0">

                <div class="px-6 py-5">
                    <div class="text-xs font-medium text-slate-400">
                        Terdaftar
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        {{ number_format($stats['registered']) }}
                    </div>
                </div>

                <div class="px-6 py-5">
                    <div class="text-xs font-medium text-slate-400">
                        Diterima
                    </div>

                    <div class="mt-2 text-2xl font-bold text-emerald-600">
                        {{ number_format($stats['accepted']) }}
                    </div>
                </div>

                <div class="px-6 py-5">
                    <div class="text-xs font-medium text-slate-400">
                        Ditolak
                    </div>

                    <div class="mt-2 text-2xl font-bold text-red-600">
                        {{ number_format($stats['rejected']) }}
                    </div>
                </div>

                <div class="px-6 py-5">
                    <div class="text-xs font-medium text-slate-400">
                        Daftar Ulang
                    </div>

                    <div class="mt-2 text-2xl font-bold text-violet-600">
                        {{ number_format($stats['reenrolled']) }}
                    </div>
                </div>

                <div class="px-6 py-5">
                    <div class="text-xs font-medium text-slate-400">
                        Mengundurkan Diri
                    </div>

                    <div class="mt-2 text-2xl font-bold text-amber-600">
                        {{ number_format($stats['withdrawn']) }}
                    </div>
                </div>

            </div>

        </section>

        {{-- Bottom --}}
        <div class="grid gap-6 lg:grid-cols-2">

            {{-- Latest Registrations --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="font-bold text-slate-900">
                        Pendaftaran Terbaru
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Lima calon siswa terbaru pada periode aktif.
                    </p>
                </div>

                @if ($latestRegistrations->isEmpty())

                    <div class="flex min-h-[220px] items-center justify-center p-6">

                        <div class="text-center">

                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                <i
                                    data-lucide="users"
                                    class="h-5 w-5"
                                ></i>
                            </div>

                            <p class="mt-4 text-sm font-semibold text-slate-700">
                                Belum ada pendaftar
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                Data terbaru akan muncul di sini.
                            </p>

                        </div>

                    </div>

                @else

                    <div class="divide-y divide-slate-100">

                        @foreach ($latestRegistrations as $registration)

                            <div class="flex items-center justify-between gap-4 px-6 py-4">

                                <div class="min-w-0">

                                    <div class="truncate text-sm font-semibold text-slate-800">
                                        {{ $registration->full_name }}
                                    </div>

                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-400">
                                        <span>
                                            {{ $registration->registration_number }}
                                        </span>

                                        @if ($registration->major)
                                            <span>•</span>
                                            <span>
                                                {{ $registration->major->code }}
                                            </span>
                                        @endif

                                        @if ($registration->admissionPath)
                                            <span>•</span>
                                            <span>
                                                {{ $registration->admissionPath->name }}
                                            </span>
                                        @endif
                                    </div>

                                </div>

                                <div class="shrink-0 text-right">

                                    @php
                                        $statusLabel = match ($registration->status) {
                                            'REGISTERED' => 'Terdaftar',
                                            'ACCEPTED' => 'Diterima',
                                            'REJECTED' => 'Ditolak',
                                            'REENROLLED' => 'Daftar Ulang',
                                            'WITHDRAWN' => 'Mengundurkan Diri',
                                            default => $registration->status,
                                        };

                                        $statusClass = match ($registration->status) {
                                            'REGISTERED' => 'bg-slate-100 text-slate-600',
                                            'ACCEPTED' => 'bg-emerald-50 text-emerald-700',
                                            'REJECTED' => 'bg-red-50 text-red-700',
                                            'REENROLLED' => 'bg-violet-50 text-violet-700',
                                            'WITHDRAWN' => 'bg-amber-50 text-amber-700',
                                            default => 'bg-slate-100 text-slate-600',
                                        };
                                    @endphp

                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold tracking-wide {{ $statusClass }}"
                                    >
                                        {{ $statusLabel }}
                                    </span>

                                    @if ($registration->registered_at)
                                        <div class="mt-1 text-[11px] text-slate-400">
                                            {{ $registration->registered_at->format('d/m/Y H:i') }}
                                        </div>
                                    @endif

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif

            </section>

            {{-- Latest Activities --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="font-bold text-slate-900">
                        Aktivitas Terbaru
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Aktivitas pendaftaran terbaru pada periode aktif.
                    </p>
                </div>

                @if ($latestActivities->isEmpty())

                    <div class="flex min-h-[220px] items-center justify-center p-6">

                        <div class="text-center">

                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                <i
                                    data-lucide="list-checks"
                                    class="h-5 w-5"
                                ></i>
                            </div>

                            <p class="mt-4 text-sm font-semibold text-slate-700">
                                Belum ada aktivitas
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                Riwayat aktivitas akan muncul di sini.
                            </p>

                        </div>

                    </div>

                @else

                    <div class="divide-y divide-slate-100">

                        @foreach ($latestActivities as $activity)

                            <div class="flex items-start gap-3 px-6 py-4">

                                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                    <i
                                        data-lucide="activity"
                                        class="h-4 w-4"
                                    ></i>
                                </div>

                                <div class="min-w-0">

                                    <div class="text-sm font-semibold text-slate-800">
                                        {{ $activity->description }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-400">
                                        {{ $activity->action }}
                                        •
                                        {{ $activity->created_at?->format('d/m/Y H:i') }}
                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif

            </section>

        </div>

    </div>
@endsection