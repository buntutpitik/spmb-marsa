@extends('layouts.app')

@section('content')
    <div class="space-y-8">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a
                    href="{{ route('admin.settings.index') }}"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 hover:text-emerald-700"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Pengaturan
                </a>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Program Khusus
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Kelola program khusus yang dapat dipilih calon siswa
                    pada formulir pendaftaran.
                </p>
            </div>

            @if ($selectedPeriod)
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        Periode Dipilih
                    </div>

                    <div class="mt-1 flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        {{ $selectedPeriod->name }}
                    </div>
                </div>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <div class="font-semibold text-red-800">
                    Periksa kembali data berikut:
                </div>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.special-programs.index') }}">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">

                    <div class="flex-1">
                        <label
                            for="period_id"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Periode SPMB
                        </label>

                        <select
                            id="period_id"
                            name="period_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
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
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Tampilkan
                    </button>

                </div>
            </form>
        </section>

        @if (! $selectedPeriod)

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                <p class="text-sm font-semibold text-amber-900">
                    Belum ada periode SPMB.
                </p>
            </div>

        @else

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="font-bold text-slate-900">
                        Tambah Program Khusus
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Program baru akan langsung tersedia pada periode
                        {{ $selectedPeriod->name }}.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('admin.special-programs.store') }}"
                    class="grid gap-5 p-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1.7fr)_120px_auto]"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="period_id"
                        value="{{ $selectedPeriod->id }}"
                    >

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Nama
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            placeholder="Contoh: Program Bahasa"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Deskripsi
                        </label>

                        <input
                            type="text"
                            name="description"
                            value="{{ old('description') }}"
                            placeholder="Opsional"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Urutan
                        </label>

                        <input
                            type="number"
                            name="sort_order"
                            value="{{ old('sort_order', ($specialPrograms->max('sort_order') ?? 0) + 1) }}"
                            min="0"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                        >
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            Tambah
                        </button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <h2 class="font-bold text-slate-900">
                                Daftar Program
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                {{ $specialPrograms->count() }} master Program Khusus.
                            </p>
                        </div>

                        <div class="text-xs text-slate-400">
                            Periode {{ $selectedPeriod->name }}
                        </div>

                    </div>

                </div>

                @if ($specialPrograms->isEmpty())

                    <div class="p-8 text-center text-sm text-slate-500">
                        Belum ada Program Khusus.
                    </div>

                @else

                    <div class="divide-y divide-slate-100">

                        @foreach ($specialPrograms as $program)

                            @php
                                $periodEnabled = $periodProgramIds->contains(
                                    (int) $program->id
                                );
                            @endphp

                            <div class="p-6">

                                <form
                                    method="POST"
                                    action="{{ route('admin.special-programs.update', $program) }}"
                                    class="grid gap-4 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1.6fr)_100px]"
                                >
                                    @csrf
                                    @method('PUT')

                                    <input
                                        type="hidden"
                                        name="period_id"
                                        value="{{ $selectedPeriod->id }}"
                                    >

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Nama
                                        </label>

                                        <input
                                            type="text"
                                            name="name"
                                            value="{{ $program->name }}"
                                            required
                                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-800 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                                        >
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Deskripsi
                                        </label>

                                        <input
                                            type="text"
                                            name="description"
                                            value="{{ $program->description }}"
                                            placeholder="Tidak ada deskripsi"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                                        >
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Urutan
                                        </label>

                                        <input
                                            type="number"
                                            name="sort_order"
                                            value="{{ $program->sort_order }}"
                                            min="0"
                                            required
                                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                                        >
                                    </div>

                                    <div class="xl:col-span-3 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">

                                        <div class="flex flex-wrap gap-2">

                                            <span
                                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold
                                                    {{ $program->is_active
                                                        ? 'bg-emerald-50 text-emerald-700'
                                                        : 'bg-slate-100 text-slate-500' }}"
                                            >
                                                Master:
                                                {{ $program->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>

                                            <span
                                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold
                                                    {{ $periodEnabled
                                                        ? 'bg-blue-50 text-blue-700'
                                                        : 'bg-amber-50 text-amber-700' }}"
                                            >
                                                {{ $selectedPeriod->name }}:
                                                {{ $periodEnabled ? 'Tersedia' : 'Tidak tersedia' }}
                                            </span>

                                        </div>

                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                        >
                                            <i data-lucide="save" class="h-4 w-4"></i>
                                            Simpan Perubahan
                                        </button>

                                    </div>
                                </form>

                                <div class="mt-3 flex flex-wrap gap-2">

                                    <form
                                        method="POST"
                                        action="{{ route('admin.special-programs.toggle-period', $program) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="hidden"
                                            name="period_id"
                                            value="{{ $selectedPeriod->id }}"
                                        >

                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-semibold transition
                                                {{ $periodEnabled
                                                    ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'
                                                    : 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100' }}"
                                        >
                                            <i
                                                data-lucide="{{ $periodEnabled ? 'eye-off' : 'eye' }}"
                                                class="h-4 w-4"
                                            ></i>

                                            {{ $periodEnabled
                                                ? 'Nonaktifkan dari Periode'
                                                : 'Aktifkan pada Periode' }}
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.special-programs.toggle-master', $program) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="hidden"
                                            name="period_id"
                                            value="{{ $selectedPeriod->id }}"
                                        >

                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-semibold transition
                                                {{ $program->is_active
                                                    ? 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                                        >
                                            <i
                                                data-lucide="{{ $program->is_active ? 'power-off' : 'power' }}"
                                                class="h-4 w-4"
                                            ></i>

                                            {{ $program->is_active
                                                ? 'Nonaktifkan Master'
                                                : 'Aktifkan Master' }}
                                        </button>
                                    </form>

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif

            </section>

            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">

                <div class="flex items-start gap-3">

                    <i
                        data-lucide="shield-check"
                        class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"
                    ></i>

                    <div>
                        <h3 class="text-sm font-semibold text-blue-900">
                            Histori pendaftaran tetap aman
                        </h3>

                        <p class="mt-1 text-sm leading-6 text-blue-800">
                            Program yang pernah dipilih calon siswa tidak dihapus
                            dari database. Jika sudah tidak digunakan, cukup
                            nonaktifkan master atau periode penggunaannya.
                        </p>
                    </div>

                </div>

            </div>

        @endif

    </div>
@endsection