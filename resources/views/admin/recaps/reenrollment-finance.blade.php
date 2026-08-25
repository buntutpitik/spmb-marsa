@extends('layouts.app')

@section('content')

    <div class="space-y-8">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>

                <div class="text-sm font-semibold text-emerald-600">
                    Rekap
                </div>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Rekap Keuangan Daftar Ulang
                </h1>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Ringkasan tagihan dan pembayaran daftar ulang per periode.
                </p>

            </div>

            <form
                method="GET"
                action="{{ route('admin.recaps.reenrollment-finance.index') }}"
            >

                <select
                    name="period_id"
                    onchange="this.form.submit()"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700"
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
                            {{ $period->is_active ? 'â€” Aktif' : '' }}
                        </option>

                    @endforeach

                </select>

            </form>

        </div>

        <div class="flex flex-wrap gap-2">

            <a
                href="{{ route('admin.recaps.index', [
                    'period_id' => $selectedPeriod?->id,
                ]) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
            >
                Rekap Jurusan
            </a>

            <a
                href="{{ route('admin.recaps.origin-schools.index', [
                    'period_id' => $selectedPeriod?->id,
                ]) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
            >
                Asal Sekolah
            </a>

            <a
                href="{{ route('admin.recaps.referrals.index', [
                    'period_id' => $selectedPeriod?->id,
                ]) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
            >
                Referral / Pembawa
            </a>

            <a
                href="{{ route('admin.recaps.reenrollment-finance.index', [
                    'period_id' => $selectedPeriod?->id,
                ]) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white"
            >
                Keuangan Daftar Ulang
            </a>

        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

            @foreach ([
                [
                    'label' => 'Total Peserta',
                    'value' => $summary['TOTAL_STUDENTS'] ?? 0,
                ],

                [
                    'label' => 'Belum Lunas',
                    'value' => $summary['WAITING'] ?? 0,
                ],

                [
                    'label' => 'Sudah Lunas',
                    'value' => $summary['PAID_OFF'] ?? 0,
                ],
            ] as $card)

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-sm font-medium text-slate-500">
                        {{ $card['label'] }}
                    </div>

                    <div class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                        {{ $card['value'] }}
                    </div>

                </div>

            @endforeach

        </div>

        <div class="grid gap-4 md:grid-cols-3">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="text-sm font-medium text-slate-500">
                    Total Tagihan
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    Rp {{ number_format(
                        $summary['TOTAL_BILLED'] ?? 0,
                        0,
                        ',',
                        '.'
                    ) }}
                </div>

            </div>

            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">

                <div class="text-sm font-medium text-blue-600">
                    Total Pembayaran Masuk
                </div>

                <div class="mt-2 text-2xl font-bold text-blue-900">
                    Rp {{ number_format(
                        $summary['TOTAL_PAID'] ?? 0,
                        0,
                        ',',
                        '.'
                    ) }}
                </div>

            </div>

            <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm">

                <div class="text-sm font-medium text-violet-600">
                    Total Sisa Tagihan
                </div>

                <div class="mt-2 text-2xl font-bold text-violet-900">
                    Rp {{ number_format(
                        $summary['TOTAL_REMAINING'] ?? 0,
                        0,
                        ',',
                        '.'
                    ) }}
                </div>

            </div>

        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <form
                method="GET"
                action="{{ route('admin.recaps.reenrollment-finance.index') }}"
                class="grid gap-3 border-b border-slate-100 p-4 lg:grid-cols-[minmax(260px,1fr)_220px_auto]"
            >

                @if ($selectedPeriod)

                    <input
                        type="hidden"
                        name="period_id"
                        value="{{ $selectedPeriod->id }}"
                    >

                @endif

                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari nama, NIK, NISN, nomor pendaftaran..."
                    class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                >

                <select
                    name="payment_status"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm"
                >
                    <option value="">
                        Semua Status
                    </option>

                    <option
                        value="WAITING"
                        @selected(request('payment_status') === 'WAITING')
                    >
                        Belum Lunas
                    </option>

                    <option
                        value="PAID_OFF"
                        @selected(request('payment_status') === 'PAID_OFF')
                    >
                        Lunas
                    </option>
                </select>

                <div class="flex gap-2">

                    <button
                        type="submit"
                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('admin.recaps.reenrollment-finance.index', [
                            'period_id' => $selectedPeriod?->id,
                        ]) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Reset
                    </a>

                </div>

            </form>

            @if ($registrations->isEmpty())

                <div class="p-10 text-center text-sm text-slate-500">
                    Belum ada data daftar ulang untuk periode ini.
                </div>

            @else

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">

                                <th class="px-6 py-3.5">
                                    Pendaftar
                                </th>

                                <th class="px-6 py-3.5">
                                    Jurusan
                                </th>

                                <th class="px-6 py-3.5 text-right">
                                    Tagihan
                                </th>

                                <th class="px-6 py-3.5 text-right">
                                    Dibayar
                                </th>

                                <th class="px-6 py-3.5 text-right">
                                    Sisa
                                </th>

                                <th class="px-6 py-3.5">
                                    Status
                                </th>

                                <th class="px-6 py-3.5 text-right">
                                    Aksi
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">

                            @foreach ($registrations as $registration)

                                @php
                                    $requiredFee =
                                        (int) (
                                            $registration->period?->default_reenroll_fee
                                            ?? 0
                                        );

                                    $totalPaid = (int) $registration
                                        ->reenrollmentPayments
                                        ->sum('amount');

                                    $remaining = max(
                                        0,
                                        $requiredFee - $totalPaid
                                    );

                                    $isPaidOff =
                                        $requiredFee > 0
                                        && $remaining === 0;
                                @endphp

                                <tr class="transition hover:bg-slate-50/80">

                                    <td class="px-6 py-4">

                                        <div class="text-sm font-semibold text-slate-800">
                                            {{ $registration->full_name }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $registration->registration_number }}
                                        </div>

                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-700">
                                        {{ $registration->major?->code ?? '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">
                                        Rp {{ number_format(
                                            $requiredFee,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-blue-700">
                                        Rp {{ number_format(
                                            $totalPaid,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold {{ $remaining === 0 ? 'text-emerald-600' : 'text-violet-700' }}">
                                        Rp {{ number_format(
                                            $remaining,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">

                                        @if ($isPaidOff)

                                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                Lunas
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                                Belum Lunas
                                            </span>

                                        @endif

                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right">

                                        <a
                                            href="{{ route(
                                                'admin.registrations.show',
                                                $registration
                                            ) }}"
                                            class="inline-flex rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                                        >
                                            Detail
                                        </a>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $registrations->links() }}
                </div>

            @endif

        </section>

    </div>

@endsection