@extends('layouts.app')

@section('content')

    <div class="space-y-8">

        {{-- Header --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>

                <div class="text-sm font-semibold text-emerald-600">
                    Data
                </div>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Rekap SPMB
                </h1>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Ringkasan pendaftaran dan distribusi calon siswa per jurusan.
                </p>

            </div>

            <form
                method="GET"
                action="{{ route('admin.recaps.index') }}"
                class="flex items-center gap-2"
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


        {{-- Navigasi Rekap --}}
        <div class="flex flex-wrap gap-2">

            <a
                href="{{ route('admin.recaps.index', [
                    'period_id' => $selectedPeriod?->id,
                ]) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white"
            >
                <i
                    data-lucide="chart-column"
                    class="h-4 w-4"
                ></i>

                Rekap Jurusan
            </a>

            <a
                href="{{ route('admin.recaps.origin-schools.index', [
                    'period_id' => $selectedPeriod?->id,
                ]) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
            >
                <i
                    data-lucide="school"
                    class="h-4 w-4"
                ></i>

                Asal Sekolah
            </a>

            <a
                href="{{ route('admin.recaps.referrals.index', [
                    'period_id' => $selectedPeriod?->id,
                ]) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
            >
                <i
                    data-lucide="users-round"
                    class="h-4 w-4"
                ></i>

                Referral / Pembawa
            </a>

            <a
                href="{{ route('admin.recaps.reenrollment-finance.index', [
                    'period_id' => $selectedPeriod?->id,
                ]) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
            >
                <i
                    data-lucide="wallet-cards"
                    class="h-4 w-4"
                ></i>

                Keuangan Daftar Ulang
            </a>

        </div>


        {{-- Summary --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

            @foreach ([
                [
                    'key' => 'TOTAL',
                    'label' => 'Total Pendaftar',
                    'icon' => 'users',
                    'iconClass' => 'bg-blue-50 text-blue-600',
                ],

                [
                    'key' => 'REGISTERED',
                    'label' => 'Terdaftar',
                    'icon' => 'user-plus',
                    'iconClass' => 'bg-slate-100 text-slate-600',
                ],

                [
                    'key' => 'ACCEPTED',
                    'label' => 'Diterima',
                    'icon' => 'circle-check-big',
                    'iconClass' => 'bg-emerald-50 text-emerald-600',
                ],

                [
                    'key' => 'REJECTED',
                    'label' => 'Ditolak',
                    'icon' => 'circle-x',
                    'iconClass' => 'bg-red-50 text-red-600',
                ],

                [
                    'key' => 'REENROLLED',
                    'label' => 'Daftar Ulang',
                    'icon' => 'wallet-cards',
                    'iconClass' => 'bg-violet-50 text-violet-600',
                ],

                [
                    'key' => 'WITHDRAWN',
                    'label' => 'Mengundurkan Diri',
                    'icon' => 'log-out',
                    'iconClass' => 'bg-amber-50 text-amber-600',
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

                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $card['iconClass'] }}">

                            <i
                                data-lucide="{{ $card['icon'] }}"
                                class="h-5 w-5"
                            ></i>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>


        {{-- Rekap Jurusan --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 px-6 py-5">

                <h2 class="font-bold text-slate-900">
                    Rekap per Jurusan
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Jumlah pendaftar berdasarkan jurusan, jenis kelamin, dan status.
                </p>

            </div>


            @if ($majorRecaps->isEmpty())

                <div class="p-8 text-center">

                    <div class="text-sm font-semibold text-slate-700">
                        Belum ada data rekap.
                    </div>

                    <div class="mt-1 text-sm text-slate-500">
                        Data akan muncul setelah terdapat pendaftar pada periode ini.
                    </div>

                </div>

            @else

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">

                                <th class="px-6 py-3.5">
                                    Jurusan
                                </th>

                                <th class="px-6 py-3.5 text-center">
                                    L
                                </th>

                                <th class="px-6 py-3.5 text-center">
                                    P
                                </th>

                                <th class="px-6 py-3.5 text-center">
                                    Total
                                </th>

                                <th class="px-6 py-3.5 text-center">
                                    Terdaftar
                                </th>

                                <th class="px-6 py-3.5 text-center">
                                    Diterima
                                </th>

                                <th class="px-6 py-3.5 text-center">
                                    Ditolak
                                </th>

                                <th class="px-6 py-3.5 text-center">
                                    Daftar Ulang
                                </th>

                                <th class="px-6 py-3.5 text-center">
                                    Mengundurkan Diri
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @foreach ($majorRecaps as $row)

                                <tr class="transition hover:bg-slate-50/80">

                                    {{-- Jurusan --}}
                                    <td class="px-6 py-4">

                                        <div class="text-sm font-semibold text-slate-800">
                                            {{ $row->code }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $row->name }}
                                        </div>

                                    </td>


                                    {{-- Laki-laki --}}
                                    <td class="px-6 py-4 text-center text-sm text-slate-700">
                                        {{ (int) $row->male }}
                                    </td>


                                    {{-- Perempuan --}}
                                    <td class="px-6 py-4 text-center text-sm text-slate-700">
                                        {{ (int) $row->female }}
                                    </td>


                                    {{-- Total --}}
                                    <td class="px-6 py-4 text-center">

                                        <span class="inline-flex min-w-8 justify-center rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-bold text-slate-800">
                                            {{ (int) $row->total }}
                                        </span>

                                    </td>


                                    {{-- Registered --}}
                                    <td class="px-6 py-4 text-center text-sm text-slate-700">
                                        {{ (int) $row->registered }}
                                    </td>


                                    {{-- Accepted --}}
                                    <td class="px-6 py-4 text-center text-sm font-semibold text-emerald-700">
                                        {{ (int) $row->accepted }}
                                    </td>


                                    {{-- Rejected --}}
                                    <td class="px-6 py-4 text-center text-sm text-red-600">
                                        {{ (int) $row->rejected }}
                                    </td>


                                    {{-- Reenrolled --}}
                                    <td class="px-6 py-4 text-center text-sm font-semibold text-violet-700">
                                        {{ (int) $row->reenrolled }}
                                    </td>


                                    {{-- Withdrawn --}}
                                    <td class="px-6 py-4 text-center text-sm text-amber-700">
                                        {{ (int) $row->withdrawn }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>


                        {{-- Total --}}
                        <tfoot class="border-t border-slate-200 bg-slate-50">

                            <tr class="font-semibold text-slate-800">

                                <td class="px-6 py-4">
                                    TOTAL
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {{ (int) $majorRecaps->sum('male') }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {{ (int) $majorRecaps->sum('female') }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {{ (int) $majorRecaps->sum('total') }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {{ (int) $majorRecaps->sum('registered') }}
                                </td>

                                <td class="px-6 py-4 text-center text-emerald-700">
                                    {{ (int) $majorRecaps->sum('accepted') }}
                                </td>

                                <td class="px-6 py-4 text-center text-red-600">
                                    {{ (int) $majorRecaps->sum('rejected') }}
                                </td>

                                <td class="px-6 py-4 text-center text-violet-700">
                                    {{ (int) $majorRecaps->sum('reenrolled') }}
                                </td>

                                <td class="px-6 py-4 text-center text-amber-700">
                                    {{ (int) $majorRecaps->sum('withdrawn') }}
                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            @endif

        </section>

    </div>

@endsection