@extends('layouts.app')

@section('content')

    <div class="space-y-8">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>

                <div class="text-sm font-semibold text-emerald-600">
                    SPMB
                </div>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Daftar Ulang
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Pantau pembayaran daftar ulang calon siswa yang telah diterima.
                </p>

            </div>

            @if ($selectedPeriod)

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">

                    <div class="text-xs text-slate-400">
                        Periode
                    </div>

                    <div class="mt-1 font-semibold text-slate-800">
                        {{ $selectedPeriod->name }}
                    </div>

                </div>

            @endif

        </div>

        <div class="grid gap-4 md:grid-cols-3">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <div class="text-sm font-medium text-slate-500">
                            Menunggu Lunas
                        </div>

                        <div class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                            {{ $summary['WAITING'] ?? 0 }}
                        </div>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <i
                            data-lucide="clock-3"
                            class="h-5 w-5"
                        ></i>
                    </div>

                </div>

            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <div class="text-sm font-medium text-slate-500">
                            Sudah Lunas
                        </div>

                        <div class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                            {{ $summary['PAID_OFF'] ?? 0 }}
                        </div>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <i
                            data-lucide="badge-check"
                            class="h-5 w-5"
                        ></i>
                    </div>

                </div>

            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <div class="text-sm font-medium text-slate-500">
                            Total Pembayaran Masuk
                        </div>

                        <div class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                            Rp {{ number_format(
                                $summary['TOTAL_RECEIVED'] ?? 0,
                                0,
                                ',',
                                '.'
                            ) }}
                        </div>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        <i
                            data-lucide="banknote"
                            class="h-5 w-5"
                        ></i>
                    </div>

                </div>

            </div>

        </div>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

            <form
                method="GET"
                action="{{ route('admin.reenrollments.index') }}"
                class="grid gap-3 border-b border-slate-100 p-4 lg:grid-cols-[minmax(240px,1.5fr)_180px_200px_auto]"
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
                    name="major_id"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                >
                    <option value="">
                        Semua Jurusan
                    </option>

                    @foreach ($majors as $major)
                        <option
                            value="{{ $major->id }}"
                            @selected(
                                (string) request('major_id')
                                === (string) $major->id
                            )
                        >
                            {{ $major->code }}
                        </option>
                    @endforeach
                </select>

                <select
                    name="payment_status"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                >
                    <option value="">
                        Semua Status Pembayaran
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
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('admin.reenrollments.index', [
                            'period_id' => $selectedPeriod?->id,
                        ]) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Reset
                    </a>

                </div>

            </form>

            @if ($registrations->isEmpty())

                <div class="p-8 text-center text-sm text-slate-500">
                    Tidak ada data daftar ulang yang sesuai filter.
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
                                    Biaya
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
                                    $requiredFee = (
                                        $registration->wave
                                        && $registration->wave->reenroll_fee !== null
                                    )
                                        ? (int) $registration->wave->reenroll_fee
                                        : (int) (
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

                                    $isPaidOff = $requiredFee > 0
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
                                        Rp {{ number_format($requiredFee, 0, ',', '.') }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-blue-700">
                                        Rp {{ number_format($totalPaid, 0, ',', '.') }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold {{ $remaining === 0 ? 'text-emerald-600' : 'text-violet-700' }}">
                                        Rp {{ number_format($remaining, 0, ',', '.') }}
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
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                                        >
                                            Detail

                                            <i
                                                data-lucide="arrow-right"
                                                class="h-3.5 w-3.5"
                                            ></i>
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