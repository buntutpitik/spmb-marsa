@extends('layouts.app')

@section('content')

    <div class="space-y-8">

        <div>
            <div class="text-sm font-semibold text-emerald-600">
                Data
            </div>

            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                Perbandingan Antar Tahun
            </h1>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Bandingkan performa pendaftaran antarperiode SPMB.
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('admin.comparison.index') }}"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            <div class="grid gap-5 md:grid-cols-2">

                <div>
                    <label
                        for="period_a"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Periode A
                    </label>

                    <select
                        id="period_a"
                        name="period_a"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="">
                            Pilih periode
                        </option>

                        @foreach ($periods as $period)
                            <option
                                value="{{ $period->id }}"
                                @selected(
                                    $periodA
                                    && $periodA->id === $period->id
                                )
                            >
                                {{ $period->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        for="period_b"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Periode B
                    </label>

                    <select
                        id="period_b"
                        name="period_b"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="">
                            Pilih periode
                        </option>

                        @foreach ($periods as $period)
                            <option
                                value="{{ $period->id }}"
                                @selected(
                                    $periodB
                                    && $periodB->id === $period->id
                                )
                            >
                                {{ $period->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="mt-5">

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                >
                    <i
                        data-lucide="git-compare-arrows"
                        class="h-4 w-4"
                    ></i>

                    Bandingkan
                </button>

            </div>
        </form>

        @if ($periodA && $periodB)
            <a
                href="{{ route('admin.comparison.export', [
                    'period_a' => $periodA->id,
                    'period_b' => $periodB->id,
                ]) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
            >
                <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                Export Excel
            </a>
        @endif

        @if ($periodA && $periodB && $comparison)

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-5">

                    <h2 class="text-lg font-bold text-slate-900">
                        Ringkasan Perbandingan
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $periodA->name }}
                        dibandingkan dengan
                        {{ $periodB->name }}.
                    </p>

                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">
                            {{ $periodA->name }}
                        </div>

                        <div class="mt-1 text-2xl font-bold text-slate-900">
                            {{ $comparison['total_a'] }}
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">
                            {{ $periodB->name }}
                        </div>

                        <div class="mt-1 text-2xl font-bold text-slate-900">
                            {{ $comparison['total_b'] }}
                        </div>
                    </div>

                    <div class="rounded-xl bg-blue-50 p-4">
                        <div class="text-sm text-blue-600">
                            Selisih
                        </div>

                        <div class="mt-1 text-2xl font-bold text-blue-900">
                            {{ ($comparison['delta'] >= 0 ? '+' : '').$comparison['delta'] }}
                        </div>
                    </div>

                    <div class="rounded-xl bg-emerald-50 p-4">
                        <div class="text-sm text-emerald-600">
                            Pertumbuhan
                        </div>

                        <div class="mt-1 text-2xl font-bold text-emerald-900">
                            @if ($comparison['growth'] === null)
                                Tidak tersedia
                            @else
                                {{ number_format(
                                    $comparison['growth'],
                                    1,
                                    ',',
                                    '.'
                                ) }}%
                            @endif
                        </div>
                    </div>

                </div>

            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h2 class="text-lg font-bold text-slate-900">
                        Status
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Perbandingan status pendaftar pada kedua periode.
                    </p>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $periodA->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $periodB->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Selisih
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share A
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share B
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Δ Share
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">

                            @foreach ($comparison['status'] as $row)

                                <tr>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-800">
                                        {{ $row['label'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['a'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['b'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ ($row['delta'] >= 0 ? '+' : '').$row['delta'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format(
                                            $row['share_a'],
                                            1,
                                            ',',
                                            '.'
                                        ) }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format(
                                            $row['share_b'],
                                            1,
                                            ',',
                                            '.'
                                        ) }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ ($row['share_delta_pp'] >= 0 ? '+' : '').number_format(
                                            $row['share_delta_pp'],
                                            1,
                                            ',',
                                            '.'
                                        ) }} pp
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </section>

        @endif

        @if (! empty($comparison['major_breakdown']))
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        Jurusan
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Perbandingan distribusi pendaftar berdasarkan jurusan pada kedua periode.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Jurusan
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $periodA->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $periodB->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Selisih
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share A
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share B
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Δ Share
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">

                            @foreach ($comparison['major_breakdown'] as $row)
                                <tr>

                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-slate-800">
                                            {{ $row['code'] }}
                                        </div>

                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $row['name'] }}
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ number_format($row['a'], 0, ',', '.') }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ number_format($row['b'], 0, ',', '.') }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ $row['delta'] >= 0 ? '+' : '' }}{{ number_format($row['delta'], 0, ',', '.') }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_a'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_b'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ $row['share_delta_pp'] >= 0 ? '+' : '' }}{{ number_format($row['share_delta_pp'], 1, ',', '.') }} pp
                                    </td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>
                </div>

            </section>
        @endif

        @if (!empty($comparison['gender_breakdown']))
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        Gender
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Perbandingan distribusi pendaftar berdasarkan gender pada kedua periode.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Gender
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $periodA->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $periodB->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Selisih
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share A
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share B
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Δ Share
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">

                            @foreach ($comparison['gender_breakdown'] as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-800">
                                        {{ $row['label'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_a'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_b'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ $row['delta'] >= 0 ? '+' : '' }}{{ $row['delta'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_a'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_b'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ $row['share_delta'] >= 0 ? '+' : '' }}{{ number_format($row['share_delta'], 1, ',', '.') }} pp
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

            </section>
        @endif

        @if (! empty($comparison['admission_path_breakdown']))
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        Jalur Pendaftaran
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Perbandingan distribusi pendaftar berdasarkan jalur pendaftaran pada kedua periode.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Jalur
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_a']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_b']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Selisih
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share A
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share B
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Δ Share
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">

                            @foreach ($comparison['admission_path_breakdown'] as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-800">
                                        {{ $row['name'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_a'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_b'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ sprintf('%+d', $row['delta']) }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_a'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_b'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ ($row['share_delta'] >= 0 ? '+' : '') . number_format($row['share_delta'], 1, ',', '.') }} pp
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

            </section>
        @endif

        @if (! empty($comparison['data_source_breakdown']))
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        Asal Data Pendaftaran
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Perbandingan pendaftaran mandiri dan input panitia
                        pada kedua periode.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Asal Data
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_a']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_b']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Selisih
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share A
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share B
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Δ Share
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($comparison['data_source_breakdown'] as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-800">
                                        {{ $row['label'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_a'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_b'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ $row['delta'] >= 0 ? '+' : '' }}{{ $row['delta'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_a'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_b'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ $row['share_delta'] >= 0 ? '+' : '' }}{{ number_format($row['share_delta'], 1, ',', '.') }} pp
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="text-sm font-semibold text-slate-500">
                    Self-Service Registration Rate
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">

                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">
                            {{ $comparison['period_a']->name }}
                        </div>

                        <div class="mt-1 text-2xl font-bold text-slate-900">
                            {{ number_format(
                                $comparison['self_service_rate']['a'],
                                1,
                                ',',
                                '.'
                            ) }}%
                        </div>
                    </div>

                    <div class="rounded-xl bg-emerald-50 p-4">
                        <div class="text-sm text-emerald-600">
                            {{ $comparison['period_b']->name }}
                        </div>

                        <div class="mt-1 text-2xl font-bold text-emerald-900">
                            {{ number_format(
                                $comparison['self_service_rate']['b'],
                                1,
                                ',',
                                '.'
                            ) }}%
                        </div>
                    </div>

                </div>
            </section>
        @endif

        @if (! empty($comparison['origin_school_breakdown']))
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        Sekolah Asal
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Perbandingan distribusi pendaftar berdasarkan sekolah asal
                        pada kedua periode.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Sekolah Asal
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_a']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_b']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Selisih
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share A
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share B
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Δ Share
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($comparison['origin_school_breakdown'] as $row)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                        {{ $row['name'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_a'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_b'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ $row['delta'] >= 0 ? '+' : '' }}{{ $row['delta'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_a'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_b'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ $row['share_delta'] >= 0 ? '+' : '' }}{{ number_format($row['share_delta'], 1, ',', '.') }} pp
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

            </section>
        @endif

        @if (! empty($comparison['referral_breakdown']))
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        Referral
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Perbandingan distribusi pendaftar berdasarkan pembawa
                        atau referral pada kedua periode.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Pembawa / Referral
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_a']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_b']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Selisih
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share A
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Share B
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Δ Share
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($comparison['referral_breakdown'] as $row)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                        {{ $row['name'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_a'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_b'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ $row['delta'] >= 0 ? '+' : '' }}{{ $row['delta'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_a'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        {{ number_format($row['share_b'], 1, ',', '.') }}%
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ ($row['share_delta'] >= 0 ? '+' : '') . number_format(
                                            $row['share_delta'],
                                            1,
                                            ',',
                                            '.'
                                        ) }} pp
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>
            </section>
        @endif

        @if (! empty($comparison['monthly_registration_trend']))
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        Tren Pendaftaran
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Perbandingan jumlah pendaftar per bulan pada kedua periode.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Bulan
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_a']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_b']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Selisih
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($comparison['monthly_registration_trend'] as $row)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                        {{ $row['label'] }}
                                    </td>

                                    <td class="px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_a'] }}
                                    </td>

                                    <td class="px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_b'] }}
                                    </td>

                                    <td class="px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                        {{ $row['delta'] >= 0 ? '+' : '' }}{{ $row['delta'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

            </section>
        @endif

        @if (! empty($comparison['registration_day_trend']))
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        Tren Sejak Pendaftaran Dibuka
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Membandingkan perkembangan pendaftar berdasarkan hari ke-
                        sejak pendaftaran masing-masing periode dibuka.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Hari
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_a']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_b']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Kumulatif A
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Kumulatif B
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Δ Kumulatif
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($comparison['registration_day_trend'] as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-800">
                                        Hari ke-{{ $row['day'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_a'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                        {{ $row['count_b'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-800">
                                        {{ $row['cumulative_a'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-800">
                                        {{ $row['cumulative_b'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold
                                        {{ $row['cumulative_delta'] > 0
                                            ? 'text-emerald-600'
                                            : ($row['cumulative_delta'] < 0
                                                ? 'text-red-600'
                                                : 'text-slate-600') }}">
                                        {{ ($row['cumulative_delta'] >= 0 ? '+' : '') . $row['cumulative_delta'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if (! empty($comparison['reenrollment_finance']))
            @php
                $finance = $comparison['reenrollment_finance'];
            @endphp

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        Daftar Ulang & Keuangan
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Perbandingan daftar ulang dan transaksi pembayaran
                        pada kedua periode.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Metrik
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_a']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $comparison['period_b']->name }}
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Selisih
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">

                            <tr>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                    Jumlah Daftar Ulang
                                </td>

                                <td class="px-6 py-4 text-right text-sm text-slate-700">
                                    {{ $finance['reenrolled_a'] }}
                                </td>

                                <td class="px-6 py-4 text-right text-sm text-slate-700">
                                    {{ $finance['reenrolled_b'] }}
                                </td>

                                <td class="px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                    {{ $finance['reenrolled_delta'] >= 0 ? '+' : '' }}{{ $finance['reenrolled_delta'] }}
                                </td>
                            </tr>

                            <tr>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                    Jumlah Transaksi
                                </td>

                                <td class="px-6 py-4 text-right text-sm text-slate-700">
                                    {{ $finance['transactions_a'] }}
                                </td>

                                <td class="px-6 py-4 text-right text-sm text-slate-700">
                                    {{ $finance['transactions_b'] }}
                                </td>

                                <td class="px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                    {{ $finance['transactions_delta'] >= 0 ? '+' : '' }}{{ $finance['transactions_delta'] }}
                                </td>
                            </tr>

                            <tr>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                    Total Pembayaran
                                </td>

                                <td class="px-6 py-4 text-right text-sm text-slate-700">
                                    Rp{{ number_format(
                                        $finance['payment_a'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td class="px-6 py-4 text-right text-sm text-slate-700">
                                    Rp{{ number_format(
                                        $finance['payment_b'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td class="px-6 py-4 text-right text-sm font-semibold text-slate-700">
                                    {{ $finance['payment_delta'] >= 0 ? '+' : '-' }}Rp{{ number_format(
                                        abs($finance['payment_delta']),
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>
                            </tr>

                        </tbody>

                    </table>
                </div>

            </section>
        @endif

    </div>

@endsection