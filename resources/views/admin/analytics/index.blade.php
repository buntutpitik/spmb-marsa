@extends('layouts.app')

@section('content')

    @php
        $maxDaily = max(
            1,
            (int) ($dailyTrend->max('total') ?? 1)
        );

        $maxMajor = max(
            1,
            (int) ($majorDistribution->max('total') ?? 1)
        );

        $maxOrigin = max(
            1,
            (int) ($topOriginSchools->max('total') ?? 1)
        );

        $maxReferral = max(
            1,
            (int) ($topReferrals->max('total') ?? 1)
        );

        $statusClasses = [
            'REGISTERED' => [
                'bar' => 'bg-slate-500',
                'text' => 'text-slate-700',
                'soft' => 'bg-slate-100',
            ],

            'ACCEPTED' => [
                'bar' => 'bg-emerald-500',
                'text' => 'text-emerald-700',
                'soft' => 'bg-emerald-50',
            ],

            'REJECTED' => [
                'bar' => 'bg-red-500',
                'text' => 'text-red-700',
                'soft' => 'bg-red-50',
            ],

            'REENROLLED' => [
                'bar' => 'bg-violet-500',
                'text' => 'text-violet-700',
                'soft' => 'bg-violet-50',
            ],

            'WITHDRAWN' => [
                'bar' => 'bg-amber-500',
                'text' => 'text-amber-700',
                'soft' => 'bg-amber-50',
            ],
        ];
    @endphp

    <div class="space-y-8">

        {{-- Header --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>

                <div class="text-sm font-semibold text-emerald-600">
                    Data
                </div>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Analitik SPMB
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Visualisasi tren pendaftaran, distribusi status, jurusan,
                    asal sekolah, dan referral.
                </p>

            </div>

            <form
                method="GET"
                action="{{ route('admin.analytics.index') }}"
            >

                <select
                    name="period_id"
                    onchange="this.form.submit()"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                >

                    @foreach ($periods as $period)

                        <option
                            value="{{ $period->id }}"
                            @selected(
                                $selectedPeriod
                                && $selectedPeriod->id === $period->id
                            )
                        >
                            {{ $period->name }}
                            {{ $period->is_active ? '— Aktif' : '' }}
                        </option>

                    @endforeach

                </select>

            </form>

        </div>


        {{-- Summary --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

            @foreach ([
                [
                    'key' => 'TOTAL',
                    'label' => 'Total Pendaftar',
                    'icon' => 'users',
                    'class' => 'bg-blue-50 text-blue-600',
                ],

                [
                    'key' => 'REGISTERED',
                    'label' => 'Terdaftar',
                    'icon' => 'user-plus',
                    'class' => 'bg-slate-100 text-slate-600',
                ],

                [
                    'key' => 'ACCEPTED',
                    'label' => 'Diterima',
                    'icon' => 'circle-check-big',
                    'class' => 'bg-emerald-50 text-emerald-600',
                ],

                [
                    'key' => 'REJECTED',
                    'label' => 'Ditolak',
                    'icon' => 'circle-x',
                    'class' => 'bg-red-50 text-red-600',
                ],

                [
                    'key' => 'REENROLLED',
                    'label' => 'Daftar Ulang',
                    'icon' => 'wallet-cards',
                    'class' => 'bg-violet-50 text-violet-600',
                ],

                [
                    'key' => 'WITHDRAWN',
                    'label' => 'Mengundurkan Diri',
                    'icon' => 'log-out',
                    'class' => 'bg-amber-50 text-amber-600',
                ],
            ] as $card)

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="flex items-center justify-between gap-4">

                        <div>

                            <div class="text-sm font-medium text-slate-500">
                                {{ $card['label'] }}
                            </div>

                            <div class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                                {{ $summary[$card['key']] ?? 0 }}
                            </div>

                        </div>

                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $card['class'] }}">
                            <i
                                data-lucide="{{ $card['icon'] }}"
                                class="h-5 w-5"
                            ></i>
                        </div>

                    </div>

                </div>

            @endforeach

        </div>


        {{-- Tren + Status --}}
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.8fr)]">

            {{-- Tren Pendaftaran --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h2 class="font-bold text-slate-900">
                        Tren Pendaftaran
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Jumlah pendaftar berdasarkan tanggal pendaftaran.
                    </p>

                </div>

                @if ($dailyTrend->isEmpty())

                    <div class="p-10 text-center text-sm text-slate-500">
                        Belum ada data tren pendaftaran.
                    </div>

                @else

                    <div class="space-y-4 p-6">

                        @foreach ($dailyTrend as $row)

                            @php
                                $dailyWidth = min(
                                    100,
                                    max(
                                        4,
                                        ((int) $row->total / $maxDaily) * 100
                                    )
                                );
                            @endphp

                            <div>

                                <div class="mb-2 flex items-center justify-between gap-4">

                                    <div class="text-sm font-medium text-slate-600">
                                        {{ \Illuminate\Support\Carbon::parse(
                                            $row->registration_date
                                        )->format('d/m/Y') }}
                                    </div>

                                    <div class="text-sm font-bold text-slate-900">
                                        {{ (int) $row->total }}
                                    </div>

                                </div>

                                <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">

                                    <div
                                        class="h-full rounded-full bg-emerald-500"
                                        style="width: {{ $dailyWidth }}%"
                                    ></div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif

            </section>


            {{-- Distribusi Status --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h2 class="font-bold text-slate-900">
                        Distribusi Status
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Komposisi status calon siswa.
                    </p>

                </div>

                <div class="space-y-4 p-6">

                    @foreach ($statusDistribution as $row)

                        @php
                            $statusConfig =
                                $statusClasses[$row['key']]
                                ?? $statusClasses['REGISTERED'];

                            $percentage =
                                ($summary['TOTAL'] ?? 0) > 0
                                    ? round(
                                        ($row['total'] / $summary['TOTAL']) * 100,
                                        1
                                    )
                                    : 0;
                        @endphp

                        <div class="rounded-xl border border-slate-100 p-4">

                            <div class="flex items-center justify-between gap-4">

                                <div class="flex items-center gap-3">

                                    <span class="h-2.5 w-2.5 rounded-full {{ $statusConfig['bar'] }}"></span>

                                    <span class="text-sm font-semibold text-slate-700">
                                        {{ $row['label'] }}
                                    </span>

                                </div>

                                <div class="text-right">

                                    <div class="text-sm font-bold text-slate-900">
                                        {{ $row['total'] }}
                                    </div>

                                    <div class="mt-0.5 text-xs text-slate-400">
                                        {{ $percentage }}%
                                    </div>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            </section>

        </div>


        {{-- Jurusan --}}
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 px-6 py-5">

                <h2 class="font-bold text-slate-900">
                    Distribusi Jurusan
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Jumlah pendaftar berdasarkan pilihan jurusan.
                </p>

            </div>

            @if ($majorDistribution->isEmpty())

                <div class="p-10 text-center text-sm text-slate-500">
                    Belum ada data jurusan.
                </div>

            @else

                <div class="grid gap-4 p-6 lg:grid-cols-2">

                    @foreach ($majorDistribution as $row)

                        @php
                            $majorWidth = min(
                                100,
                                max(
                                    4,
                                    ((int) $row->total / $maxMajor) * 100
                                )
                            );
                        @endphp

                        <div class="rounded-xl border border-slate-100 p-4">

                            <div class="flex items-start justify-between gap-4">

                                <div>

                                    <div class="text-sm font-bold text-slate-800">
                                        {{ $row->code }}
                                    </div>

                                    <div class="mt-1 text-xs leading-5 text-slate-400">
                                        {{ $row->name }}
                                    </div>

                                </div>

                                <div class="text-xl font-bold text-slate-900">
                                    {{ (int) $row->total }}
                                </div>

                            </div>

                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">

                                <div
                                    class="h-full rounded-full bg-blue-500"
                                    style="width: {{ $majorWidth }}%"
                                ></div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @endif

        </section>


        {{-- Asal Sekolah + Referral --}}
        <div class="grid gap-6 xl:grid-cols-2">

            {{-- Top Origin Schools --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h2 class="font-bold text-slate-900">
                        Asal Sekolah Teratas
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Maksimal 10 sekolah dengan pendaftar terbanyak.
                    </p>

                </div>

                @if ($topOriginSchools->isEmpty())

                    <div class="p-10 text-center text-sm text-slate-500">
                        Belum ada data asal sekolah.
                    </div>

                @else

                    <div class="space-y-4 p-6">

                        @foreach ($topOriginSchools as $row)

                            @php
                                $originWidth = min(
                                    100,
                                    max(
                                        4,
                                        ((int) $row->total / $maxOrigin) * 100
                                    )
                                );
                            @endphp

                            <div>

                                <div class="mb-2 flex items-center justify-between gap-4">

                                    <div class="truncate text-sm font-semibold text-slate-700">
                                        {{ $row->origin_school }}
                                    </div>

                                    <div class="text-sm font-bold text-slate-900">
                                        {{ (int) $row->total }}
                                    </div>

                                </div>

                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">

                                    <div
                                        class="h-full rounded-full bg-violet-500"
                                        style="width: {{ $originWidth }}%"
                                    ></div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif

            </section>


            {{-- Top Referral --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h2 class="font-bold text-slate-900">
                        Referral Teratas
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Maksimal 10 pembawa / sumber referral terbanyak.
                    </p>

                </div>

                @if ($topReferrals->isEmpty())

                    <div class="p-10 text-center text-sm text-slate-500">
                        Belum ada data referral.
                    </div>

                @else

                    <div class="space-y-4 p-6">

                        @foreach ($topReferrals as $row)

                            @php
                                $referralWidth = min(
                                    100,
                                    max(
                                        4,
                                        ((int) $row->total / $maxReferral) * 100
                                    )
                                );
                            @endphp

                            <div>

                                <div class="mb-2 flex items-start justify-between gap-4">

                                    <div class="min-w-0">

                                        <div class="truncate text-sm font-semibold text-slate-700">
                                            {{ $row->referrer_name_label }}
                                        </div>

                                        <div class="mt-1 truncate text-xs text-slate-400">
                                            {{ $row->referrer_source_label }}
                                        </div>

                                    </div>

                                    <div class="text-sm font-bold text-slate-900">
                                        {{ (int) $row->total }}
                                    </div>

                                </div>

                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">

                                    <div
                                        class="h-full rounded-full bg-amber-500"
                                        style="width: {{ $referralWidth }}%"
                                    ></div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif

            </section>

        </div>

    </div>

@endsection