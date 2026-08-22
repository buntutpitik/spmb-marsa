@extends('layouts.app')

@section('content')

    <div class="space-y-8">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>

                <div class="text-sm font-semibold text-emerald-600">
                    Rekap
                </div>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Rekap Referral / Pembawa
                </h1>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Distribusi pendaftar berdasarkan nama pembawa dan sumber referral.
                </p>

            </div>

            <form
                method="GET"
                action="{{ route('admin.recaps.referrals.index') }}"
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
                            {{ $period->is_active ? '— Aktif' : '' }}
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
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white"
            >
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

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 px-6 py-5">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div>

                        <h2 class="font-bold text-slate-900">
                            Distribusi Referral
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Diurutkan berdasarkan jumlah pendaftar terbanyak.
                        </p>

                    </div>

                    <form
                        method="GET"
                        action="{{ route('admin.recaps.referrals.index') }}"
                        class="flex w-full gap-2 lg:w-auto"
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
                            placeholder="Cari pembawa / sumber..."
                            class="min-w-0 flex-1 rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 lg:w-72"
                        >

                        <button
                            type="submit"
                            class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            Cari
                        </button>

                        @if (request()->filled('q'))

                            <a
                                href="{{ route('admin.recaps.referrals.index', [
                                    'period_id' => $selectedPeriod?->id,
                                ]) }}"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                            >
                                Reset
                            </a>

                        @endif

                    </form>

                </div>

            </div>

            @if ($referralRecaps->isEmpty())

                <div class="p-10 text-center">

                    <div class="text-sm font-semibold text-slate-700">
                        Belum ada data referral.
                    </div>

                    <div class="mt-1 text-sm text-slate-500">
                        Data akan muncul setelah pendaftar memiliki informasi pembawa atau sumber referral.
                    </div>

                </div>

            @else

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">

                                <th class="px-6 py-3.5">
                                    No.
                                </th>

                                <th class="px-6 py-3.5">
                                    Nama Pembawa
                                </th>

                                <th class="px-6 py-3.5">
                                    Sumber Referral
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

                            @foreach ($referralRecaps as $row)

                                <tr class="transition hover:bg-slate-50/80">

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-400">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                        {{ $row->referrer_name_label }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        {{ $row->referrer_source_label }}
                                    </td>

                                    <td class="px-6 py-4 text-center">

                                        <span class="inline-flex min-w-8 justify-center rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-bold text-slate-800">
                                            {{ (int) $row->total }}
                                        </span>

                                    </td>

                                    <td class="px-6 py-4 text-center text-sm text-slate-700">
                                        {{ (int) $row->registered }}
                                    </td>

                                    <td class="px-6 py-4 text-center text-sm font-semibold text-emerald-700">
                                        {{ (int) $row->accepted }}
                                    </td>

                                    <td class="px-6 py-4 text-center text-sm text-red-600">
                                        {{ (int) $row->rejected }}
                                    </td>

                                    <td class="px-6 py-4 text-center text-sm font-semibold text-violet-700">
                                        {{ (int) $row->reenrolled }}
                                    </td>

                                    <td class="px-6 py-4 text-center text-sm text-amber-700">
                                        {{ (int) $row->withdrawn }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot class="border-t border-slate-200 bg-slate-50">

                            <tr class="font-semibold text-slate-800">

                                <td class="px-6 py-4"></td>

                                <td
                                    class="px-6 py-4"
                                    colspan="2"
                                >
                                    TOTAL
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {{ (int) $referralRecaps->sum('total') }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {{ (int) $referralRecaps->sum('registered') }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {{ (int) $referralRecaps->sum('accepted') }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {{ (int) $referralRecaps->sum('rejected') }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {{ (int) $referralRecaps->sum('reenrolled') }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {{ (int) $referralRecaps->sum('withdrawn') }}
                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            @endif

        </section>

    </div>

@endsection