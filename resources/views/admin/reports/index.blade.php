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
                    Pusat Laporan
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Pilih laporan SPMB yang ingin ditampilkan atau diekspor.
                </p>

            </div>

            <form
                method="GET"
                action="{{ route('admin.reports.index') }}"
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


        {{-- Ringkasan --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="text-sm font-medium text-slate-500">
                    Total Pendaftar
                </div>

                <div class="mt-2 text-3xl font-bold text-slate-900">
                    {{ $summary['TOTAL'] ?? 0 }}
                </div>

            </div>

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">

                <div class="text-sm font-medium text-emerald-600">
                    Diterima
                </div>

                <div class="mt-2 text-3xl font-bold text-emerald-900">
                    {{ $summary['ACCEPTED'] ?? 0 }}
                </div>

            </div>

            <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm">

                <div class="text-sm font-medium text-violet-600">
                    Daftar Ulang
                </div>

                <div class="mt-2 text-3xl font-bold text-violet-900">
                    {{ $summary['REENROLLED'] ?? 0 }}
                </div>

            </div>

            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">

                <div class="text-sm font-medium text-blue-600">
                    Pembayaran Masuk
                </div>

                <div class="mt-2 text-2xl font-bold text-blue-900">
                    Rp {{ number_format(
                        $summary['TOTAL_PAYMENT'] ?? 0,
                        0,
                        ',',
                        '.'
                    ) }}
                </div>

            </div>

        </div>


        {{-- Jenis Laporan --}}
        <section>

            <div class="mb-4">

                <h2 class="text-lg font-bold text-slate-900">
                    Jenis Laporan
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Pilih jenis data yang akan digunakan.
                </p>

            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">

                {{-- Pendaftaran --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <i
                            data-lucide="users"
                            class="h-5 w-5"
                        ></i>
                    </div>

                    <h3 class="mt-5 font-bold text-slate-900">
                        Data Pendaftar
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Biodata dan informasi seluruh pendaftar pada periode terpilih.
                    </p>

                    <div class="mt-5 rounded-xl bg-slate-50 px-4 py-3">

                        <div class="text-xs text-slate-400">
                            Jumlah Data
                        </div>

                        <div class="mt-1 text-lg font-bold text-slate-900">
                            {{ $summary['TOTAL'] ?? 0 }}
                        </div>

                    </div>

                    <div class="mt-5 flex gap-2">

                        <a
                            href="{{ route('admin.reports.registrations.excel', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            Excel
                        </a>

                        <a
                            href="{{ route('admin.reports.registrations.pdf', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            PDF
                        </a>

                    </div>

                </div>


                {{-- Penerimaan --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <i
                            data-lucide="circle-check-big"
                            class="h-5 w-5"
                        ></i>
                    </div>

                    <h3 class="mt-5 font-bold text-slate-900">
                        Laporan Penerimaan
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Data status diterima, ditolak, dan proses seleksi calon siswa.
                    </p>

                    <div class="mt-5 grid grid-cols-2 gap-2">

                        <div class="rounded-xl bg-emerald-50 px-3 py-3">

                            <div class="text-xs text-emerald-600">
                                Diterima
                            </div>

                            <div class="mt-1 font-bold text-emerald-900">
                                {{ $summary['ACCEPTED'] ?? 0 }}
                            </div>

                        </div>

                        <div class="rounded-xl bg-red-50 px-3 py-3">

                            <div class="text-xs text-red-600">
                                Ditolak
                            </div>

                            <div class="mt-1 font-bold text-red-900">
                                {{ $summary['REJECTED'] ?? 0 }}
                            </div>

                        </div>

                    </div>

                    <div class="mt-5 flex gap-2">

                        <a
                            href="{{ route('admin.reports.admissions.excel', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            Excel
                        </a>

                        <a
                            href="{{ route('admin.reports.admissions.pdf', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            PDF
                        </a>

                    </div>

                </div>


                {{-- Daftar Ulang --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        <i
                            data-lucide="wallet-cards"
                            class="h-5 w-5"
                        ></i>
                    </div>

                    <h3 class="mt-5 font-bold text-slate-900">
                        Daftar Ulang & Keuangan
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Rekap pembayaran daftar ulang, status lunas, dan transaksi.
                    </p>

                    <div class="mt-5 rounded-xl bg-violet-50 px-4 py-3">

                        <div class="text-xs text-violet-600">
                            Pembayaran Masuk
                        </div>

                        <div class="mt-1 font-bold text-violet-900">
                            Rp {{ number_format(
                                $summary['TOTAL_PAYMENT'] ?? 0,
                                0,
                                ',',
                                '.'
                            ) }}
                        </div>

                    </div>

                    <div class="mt-5 flex gap-2">

                        <a
                            href="{{ route('admin.reports.reenrollment-finance.excel', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            Excel
                        </a>

                        <a
                            href="{{ route('admin.reports.reenrollment-finance.pdf', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            PDF
                        </a>

                    </div>

                </div>


                {{-- Rekap Jurusan --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                        <i
                            data-lucide="chart-column"
                            class="h-5 w-5"
                        ></i>
                    </div>

                    <h3 class="mt-5 font-bold text-slate-900">
                        Rekap Jurusan
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Distribusi pendaftar berdasarkan jurusan, gender, dan status.
                    </p>

                    <div class="mt-5 flex gap-2">

                        <a
                            href="{{ route('admin.reports.major-recap.excel', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            Excel
                        </a>

                        <a
                            href="{{ route('admin.reports.major-recap.pdf', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            PDF
                        </a>

                    </div>

                </div>


                {{-- Asal Sekolah --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <i
                            data-lucide="school"
                            class="h-5 w-5"
                        ></i>
                    </div>

                    <h3 class="mt-5 font-bold text-slate-900">
                        Rekap Asal Sekolah
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Distribusi pendaftar berdasarkan SMP/MTs atau sekolah asal.
                    </p>

                    <div class="mt-5 flex gap-2">

                        <a
                            href="{{ route('admin.reports.origin-school-recap.excel', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            Excel
                        </a>

                        <a
                            href="{{ route('admin.reports.origin-school-recap.pdf', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            PDF
                        </a>

                    </div>

                </div>


                {{-- Referral --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                        <i
                            data-lucide="users-round"
                            class="h-5 w-5"
                        ></i>
                    </div>

                    <h3 class="mt-5 font-bold text-slate-900">
                        Referral / Pembawa
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Rekap nama pembawa, sumber referral, dan hasil pendaftarannya.
                    </p>

                    <div class="mt-5 flex gap-2">

                        <a
                            href="{{ route('admin.reports.referral-recap.excel', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            Excel
                        </a>

                        <a
                            href="{{ route('admin.reports.referral-recap.pdf', [
                                'period_id' => $selectedPeriod?->id,
                            ]) }}"
                            class="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            PDF
                        </a>

                    </div>

                </div>

            </div>

        </section>

    </div>

@endsection