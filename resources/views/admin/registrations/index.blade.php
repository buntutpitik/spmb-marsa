@extends('layouts.app')

@section('content')
    <div class="space-y-8">

        {{-- Heading --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>
                <p class="text-sm font-semibold text-emerald-600">
                    SPMB
                </p>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Data Pendaftaran
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Kelola dan pantau data calon siswa pada setiap periode SPMB.
                </p>
            </div>

            @if (
                $selectedPeriod
                && $selectedPeriod->is_active
                && $selectedPeriod->status === 'OPEN'
            )
                <a
                    href="{{ route('admin.registrations.create', [
                        'period_id' => $selectedPeriod->id,
                    ]) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                >
                    <i
                        data-lucide="user-plus"
                        class="h-4 w-4"
                    ></i>

                    Tambah Pendaftar
                </a>
            @endif
            @if ($selectedPeriod)
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">

                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        Periode Dipilih
                    </div>

                    <div class="mt-1 flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <span
                            class="h-2 w-2 rounded-full {{ $selectedPeriod->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"
                        ></span>

                        {{ $selectedPeriod->name }}
                    </div>

                </div>
            @endif

        </div>

        {{-- Filter --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">

            <form
                method="GET"
                action="{{ route('admin.registrations.index') }}"
                class="space-y-4"
            >

                {{-- Search --}}
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end">

                    <div class="min-w-0 flex-1">

                        <label
                            for="q"
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                        >
                            Pencarian
                        </label>

                        <div class="relative">

                            <i
                                data-lucide="search"
                                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            ></i>

                            <input
                                id="q"
                                type="text"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="Nama, nomor pendaftaran, NIK, NISN, sekolah asal..."
                                class="w-full rounded-xl border border-slate-300 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >

                        </div>

                    </div>

                    <div class="flex shrink-0 gap-2">

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            <i
                                data-lucide="search"
                                class="h-4 w-4"
                            ></i>

                            Terapkan
                        </button>

                        <a
                            href="{{ route(
                                'admin.registrations.index',
                                $selectedPeriod
                                    ? ['period_id' => $selectedPeriod->id]
                                    : []
                            ) }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </div>

                {{-- Compact Filters --}}
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">

                    {{-- Period --}}
                    <div>

                        <label
                            for="period_id"
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                        >
                            Periode
                        </label>

                        <select
                            id="period_id"
                            name="period_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
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
                                    {{ $period->is_active ? '- Aktif' : '' }}
                                </option>

                            @endforeach
                        </select>

                    </div>

                    {{-- Status --}}
                    <div>

                        <label
                            for="status"
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                        >
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                        >
                            <option value="">
                                Semua Status
                            </option>

                            @foreach ($statuses as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(request('status') === $value)
                                >
                                    {{ $label }}
                                </option>

                            @endforeach
                        </select>

                    </div>

                    {{-- Major --}}
                    <div>

                        <label
                            for="major_id"
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                        >
                            Jurusan
                        </label>

                        <select
                            id="major_id"
                            name="major_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
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

                    {{-- Admission Path --}}
                    <div>

                        <label
                            for="admission_path_id"
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                        >
                            Jalur
                        </label>

                        <select
                            id="admission_path_id"
                            name="admission_path_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
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

                </div>

            </form>

        </section>

        {{-- Table --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <h2 class="font-bold text-slate-900">
                        Daftar Pendaftar
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ number_format($registrations->total()) }}
                        data ditemukan.
                    </p>

                </div>

                @if ($selectedPeriod)

                    <span class="text-xs font-medium text-slate-400">
                        {{ $selectedPeriod->name }}
                    </span>

                @endif

            </div>

            @if ($registrations->isEmpty())

                <div class="flex min-h-[260px] items-center justify-center p-8">

                    <div class="text-center">

                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <i
                                data-lucide="users"
                                class="h-6 w-6"
                            ></i>
                        </div>

                        <h3 class="mt-4 font-semibold text-slate-800">
                            Data tidak ditemukan
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Belum ada pendaftar atau filter tidak menemukan data.
                        </p>

                    </div>

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

                                <th class="px-6 py-3.5">
                                    Jalur
                                </th>

                                <th class="px-6 py-3.5">
                                    Status
                                </th>

                                <th class="px-6 py-3.5">
                                    Tanggal
                                </th>

                                <th class="px-6 py-3.5 text-right">
                                    Aksi
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">

                            @foreach ($registrations as $registration)

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
                                        'REGISTERED' => 'bg-slate-100 text-slate-700',
                                        'ACCEPTED' => 'bg-emerald-50 text-emerald-700',
                                        'REJECTED' => 'bg-red-50 text-red-700',
                                        'REENROLLED' => 'bg-violet-50 text-violet-700',
                                        'WITHDRAWN' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp

                                <tr class="transition hover:bg-slate-50/70">

                                    <td class="px-6 py-4">

                                        <div class="min-w-[230px]">

                                            <div class="font-semibold text-slate-900">
                                                {{ $registration->full_name }}
                                            </div>

                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $registration->registration_number }}
                                            </div>

                                            <div class="mt-1 text-xs text-slate-400">
                                                NIK: {{ $registration->nik }}
                                            </div>

                                        </div>

                                    </td>

                                    <td class="px-6 py-4 text-sm text-slate-700">

                                        @if ($registration->major)

                                            <div class="font-semibold">
                                                {{ $registration->major->code }}
                                            </div>

                                            <div class="mt-1 max-w-[220px] text-xs text-slate-400">
                                                {{ $registration->major->name }}
                                            </div>

                                        @else
                                            -
                                        @endif

                                    </td>

                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        {{ $registration->admissionPath?->name ?? '-' }}
                                    </td>

                                    <td class="px-6 py-4">

                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>

                                    </td>

                                    <td class="px-6 py-4 text-sm text-slate-600">

                                        @if ($registration->registered_at)

                                            {{ $registration->registered_at->format('d/m/Y') }}

                                            <div class="mt-1 text-xs text-slate-400">
                                                {{ $registration->registered_at->format('H:i') }}
                                            </div>

                                        @else
                                            -
                                        @endif

                                    </td>

                                    <td class="px-6 py-4 text-right">

                                        <a
                                            href="{{ route('admin.registrations.show', [
                                                'registration' => $registration,
                                                'period_id' => $selectedPeriod->id,
                                            ]) }}"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                                        >
                                            <i
                                                data-lucide="eye"
                                                class="h-4 w-4"
                                            ></i>

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