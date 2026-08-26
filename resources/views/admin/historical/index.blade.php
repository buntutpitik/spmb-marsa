@extends('layouts.app')

@section('content')

    <div class="space-y-8">

        {{-- Header --}}
        <div>
            <div class="text-sm font-semibold text-emerald-600">
                Data
            </div>

            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                Data Historis
            </h1>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Akses arsip SPMB dari periode yang telah ditutup.
                Data historis tersedia dalam mode baca saja.
            </p>
        </div>

        {{-- Information --}}
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <div class="flex items-start gap-3">

                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <i
                        data-lucide="archive"
                        class="h-5 w-5"
                    ></i>
                </div>

                <div>
                    <div class="font-bold text-amber-900">
                        Arsip SPMB
                    </div>

                    <p class="mt-1 text-sm leading-6 text-amber-800">
                        Periode yang telah ditutup tetap dapat ditinjau,
                        dianalisis, dan diekspor tanpa mengubah data aslinya.
                    </p>
                </div>

            </div>
        </div>

        {{-- Historical periods --}}
        @if ($periods->isEmpty())

            <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                    <i
                        data-lucide="archive-x"
                        class="h-5 w-5"
                    ></i>
                </div>

                <div class="mt-4 font-bold text-slate-800">
                    Belum ada data historis
                </div>

                <p class="mt-1 text-sm text-slate-500">
                    Periode yang telah ditutup akan tampil di halaman ini.
                </p>

            </div>

        @else

            <div class="grid gap-5 xl:grid-cols-2">

                @foreach ($periods as $period)

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-100 px-6 py-5">

                            <div class="flex items-start justify-between gap-4">

                                <div>
                                    <div class="flex flex-wrap items-center gap-2">

                                        <h2 class="text-xl font-bold text-slate-900">
                                            {{ $period->name }}
                                        </h2>

                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                                            {{ $period->status }}
                                        </span>

                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">
                                            HISTORIS
                                        </span>

                                    </div>

                                    <p class="mt-2 text-sm text-slate-500">
                                        Tahun ajaran {{ $period->year_start }}/{{ $period->year_end }}
                                    </p>
                                </div>

                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                    <i
                                        data-lucide="archive"
                                        class="h-5 w-5"
                                    ></i>
                                </div>

                            </div>

                        </div>

                        <div class="p-6">

                            <div class="rounded-xl bg-slate-50 px-4 py-4">

                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Total Data
                                </div>

                                <div class="mt-1 text-2xl font-bold text-slate-900">
                                    {{ $period->registrations_count }} Pendaftar
                                </div>

                            </div>

                            <div class="mt-5 grid gap-2 sm:grid-cols-2">

                                <a
                                    href="{{ route('admin.registrations.index', [
                                        'period_id' => $period->id,
                                    ]) }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    <i
                                        data-lucide="users"
                                        class="h-4 w-4"
                                    ></i>

                                    Data Pendaftar
                                </a>

                                <a
                                    href="{{ route('admin.recaps.index', [
                                        'period_id' => $period->id,
                                    ]) }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    <i
                                        data-lucide="chart-column-big"
                                        class="h-4 w-4"
                                    ></i>

                                    Rekap
                                </a>

                                <a
                                    href="{{ route('admin.analytics.index', [
                                        'period_id' => $period->id,
                                    ]) }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    <i
                                        data-lucide="chart-no-axes-combined"
                                        class="h-4 w-4"
                                    ></i>

                                    Analitik
                                </a>

                                <a
                                    href="{{ route('admin.reports.index', [
                                        'period_id' => $period->id,
                                    ]) }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                >
                                    <i
                                        data-lucide="file-text"
                                        class="h-4 w-4"
                                    ></i>

                                    Laporan
                                </a>

                            </div>

                        </div>

                    </section>

                @endforeach

            </div>

        @endif

    </div>

@endsection