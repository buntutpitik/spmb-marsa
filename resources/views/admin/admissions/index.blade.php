@extends('layouts.app')

@section('content')

    @php
        $statusLabels = [
            'REGISTERED' => 'Menunggu Seleksi',
            'ACCEPTED' => 'Diterima',
            'REJECTED' => 'Ditolak',
            'WITHDRAWN' => 'Mengundurkan Diri',
        ];

        $statusClasses = [
            'REGISTERED' => 'bg-slate-100 text-slate-700',
            'ACCEPTED' => 'bg-emerald-50 text-emerald-700',
            'REJECTED' => 'bg-red-50 text-red-700',
            'WITHDRAWN' => 'bg-amber-50 text-amber-700',
        ];
    @endphp

    <div class="space-y-8">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>

                <div class="text-sm font-semibold text-emerald-600">
                    SPMB
                </div>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Penerimaan
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Kelola proses seleksi calon siswa pada periode SPMB.
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

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            @foreach ([
                'REGISTERED' => [
                    'label' => 'Menunggu Seleksi',
                    'icon' => 'clock-3',
                    'class' => 'bg-slate-50 text-slate-600',
                ],

                'ACCEPTED' => [
                    'label' => 'Diterima',
                    'icon' => 'circle-check-big',
                    'class' => 'bg-emerald-50 text-emerald-600',
                ],

                'REJECTED' => [
                    'label' => 'Ditolak',
                    'icon' => 'circle-x',
                    'class' => 'bg-red-50 text-red-600',
                ],

                'WITHDRAWN' => [
                    'label' => 'Mengundurkan Diri',
                    'icon' => 'log-out',
                    'class' => 'bg-amber-50 text-amber-600',
                ],
            ] as $status => $card)

                <a
                    href="{{ route('admin.admissions.index', [
                        'period_id' => $selectedPeriod?->id,
                        'status' => $status,
                    ]) }}"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >

                    <div class="flex items-center justify-between gap-4">

                        <div>

                            <div class="text-sm font-medium text-slate-500">
                                {{ $card['label'] }}
                            </div>

                            <div class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                                {{ $counts[$status] ?? 0 }}
                            </div>

                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $card['class'] }}">
                            <i
                                data-lucide="{{ $card['icon'] }}"
                                class="h-5 w-5"
                            ></i>
                        </div>

                    </div>

                </a>

            @endforeach

        </div>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

            <form
                method="GET"
                action="{{ route('admin.admissions.index') }}"
                class="grid gap-3 border-b border-slate-100 p-4 lg:grid-cols-[minmax(220px,1.5fr)_180px_180px_180px_auto]"
            >

                @if ($selectedPeriod)
                    <input
                        type="hidden"
                        name="period_id"
                        value="{{ $selectedPeriod->id }}"
                    >
                @endif

                <div>

                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari nama, NIK, NISN, nomor pendaftaran..."
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >

                </div>

                <div>

                    <select
                        name="status"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                        <option value="">
                            Semua Status
                        </option>

                        @foreach ($statusLabels as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(request('status') === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                </div>

                <div>

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

                </div>

                <div>

                    <select
                        name="admission_path_id"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                        <option value="">
                            Semua Jalur
                        </option>

                        @foreach ($admissionPaths as $path)
                            <option
                                value="{{ $path->id }}"
                                @selected(
                                    (string) request('admission_path_id')
                                    === (string) $path->id
                                )
                            >
                                {{ $path->name }}
                            </option>
                        @endforeach
                    </select>

                </div>

                <div class="flex gap-2">

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    >
                        <i
                            data-lucide="search"
                            class="h-4 w-4"
                        ></i>

                        Filter
                    </button>

                    <a
                        href="{{ route('admin.admissions.index', [
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
                    Tidak ada data penerimaan yang sesuai filter.
                </div>

            @else

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3.5">
                                    No. Pendaftaran
                                </th>

                                <th class="px-6 py-3.5">
                                    Nama
                                </th>

                                <th class="px-6 py-3.5">
                                    Jurusan
                                </th>

                                <th class="px-6 py-3.5">
                                    Jalur
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

                                <tr class="transition hover:bg-slate-50/80">

                                    <td class="whitespace-nowrap px-6 py-4">

                                        <div class="text-sm font-semibold text-slate-800">
                                            {{ $registration->registration_number }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $registration->registered_at?->format('d/m/Y H:i') }}
                                        </div>

                                    </td>

                                    <td class="px-6 py-4">

                                        <div class="text-sm font-semibold text-slate-800">
                                            {{ $registration->full_name }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $registration->origin_school ?: '-' }}
                                        </div>

                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-700">
                                        {{ $registration->major?->code ?? '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                        {{ $registration->admissionPath?->name ?? '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">

                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$registration->status] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ $statusLabels[$registration->status] ?? $registration->status }}
                                        </span>

                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right">

                                        <a
                                            href="{{ route(
                                                        'admin.registrations.show',
                                                        [
                                                            'registration' => $registration,
                                                            'period_id' => $selectedPeriod->id,
                                                        ]
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