@extends('layouts.app')

@section('content')
    <div class="space-y-8">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>
                <a
                    href="{{ route('admin.settings.index') }}"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 hover:text-emerald-700"
                >
                    <i
                        data-lucide="arrow-left"
                        class="h-4 w-4"
                    ></i>

                    Pengaturan
                </a>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Asal Sekolah
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Kelola daftar SMP/MTs yang tersedia pada pilihan
                    Asal Sekolah di formulir pendaftaran.
                </p>
            </div>

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

        {{-- Tambah --}}
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 px-6 py-5">

                <h2 class="font-bold text-slate-900">
                    Tambah Asal Sekolah
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Sekolah baru akan langsung aktif dan tersedia
                    pada formulir pendaftaran.
                </p>

            </div>

            <form
                method="POST"
                action="{{ route('admin.origin-schools.store') }}"
                class="grid gap-5 p-6 lg:grid-cols-[minmax(0,1.8fr)_180px_120px_auto]"
            >
                @csrf

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Nama Sekolah
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        placeholder="Contoh: SMP NEGERI 1 KEBUMEN"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm uppercase focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >

                </div>

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Jenis
                    </label>

                    <select
                        name="type"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                        <option value="">
                            Tidak ditentukan
                        </option>

                        <option
                            value="SMP"
                            @selected(old('type') === 'SMP')
                        >
                            SMP
                        </option>

                        <option
                            value="MTs"
                            @selected(old('type') === 'MTs')
                        >
                            MTs
                        </option>

                        <option
                            value="LAINNYA"
                            @selected(old('type') === 'LAINNYA')
                        >
                            Lainnya
                        </option>
                    </select>

                </div>

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Urutan
                    </label>

                    <input
                        type="number"
                        name="sort_order"
                        value="{{ old(
                            'sort_order',
                            ($originSchools->max('sort_order') ?? 0) + 1
                        ) }}"
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
                        <i
                            data-lucide="plus"
                            class="h-4 w-4"
                        ></i>

                        Tambah
                    </button>

                </div>

            </form>

        </section>

        {{-- List --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">

                <div>
                    <h2 class="font-bold text-slate-900">
                        Daftar Asal Sekolah
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $originSchools->count() }} sekolah pada master.
                    </p>
                </div>

            </div>

            @if ($originSchools->isEmpty())

                <div class="p-8 text-center text-sm text-slate-500">
                    Belum ada data asal sekolah.
                </div>

            @else

                <div class="divide-y divide-slate-100">

                    @foreach ($originSchools as $school)

                        <div class="p-6">

                            <form
                                method="POST"
                                action="{{ route(
                                    'admin.origin-schools.update',
                                    $school
                                ) }}"
                                class="grid gap-4 xl:grid-cols-[minmax(0,1.8fr)_180px_100px]"
                            >
                                @csrf
                                @method('PUT')

                                <div>

                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Nama Sekolah
                                    </label>

                                    <input
                                        type="text"
                                        name="name"
                                        value="{{ $school->name }}"
                                        required
                                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium uppercase text-slate-800 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                                    >

                                </div>

                                <div>

                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Jenis
                                    </label>

                                    <select
                                        name="type"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                                    >
                                        <option
                                            value=""
                                            @selected(! $school->type)
                                        >
                                            Tidak ditentukan
                                        </option>

                                        <option
                                            value="SMP"
                                            @selected($school->type === 'SMP')
                                        >
                                            SMP
                                        </option>

                                        <option
                                            value="MTs"
                                            @selected($school->type === 'MTs')
                                        >
                                            MTs
                                        </option>

                                        <option
                                            value="LAINNYA"
                                            @selected($school->type === 'LAINNYA')
                                        >
                                            Lainnya
                                        </option>
                                    </select>

                                </div>

                                <div>

                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Urutan
                                    </label>

                                    <input
                                        type="number"
                                        name="sort_order"
                                        value="{{ $school->sort_order }}"
                                        min="0"
                                        required
                                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                                    >

                                </div>

                                <div class="xl:col-span-3 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">

                                    <span
                                        class="inline-flex self-start rounded-full px-3 py-1 text-xs font-semibold
                                            {{ $school->is_active
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-slate-100 text-slate-500' }}"
                                    >
                                        {{ $school->is_active
                                            ? 'Aktif'
                                            : 'Nonaktif' }}
                                    </span>

                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        <i
                                            data-lucide="save"
                                            class="h-4 w-4"
                                        ></i>

                                        Simpan Perubahan
                                    </button>

                                </div>

                            </form>

                            <div class="mt-3">

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'admin.origin-schools.toggle',
                                        $school
                                    ) }}"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-semibold transition
                                            {{ $school->is_active
                                                ? 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                                : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                                    >
                                        <i
                                            data-lucide="{{ $school->is_active
                                                ? 'power-off'
                                                : 'power' }}"
                                            class="h-4 w-4"
                                        ></i>

                                        {{ $school->is_active
                                            ? 'Nonaktifkan'
                                            : 'Aktifkan' }}
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
                    data-lucide="info"
                    class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"
                ></i>

                <div>

                    <h3 class="text-sm font-semibold text-blue-900">
                        Data historis tetap aman
                    </h3>

                    <p class="mt-1 text-sm leading-6 text-blue-800">
                        Nama asal sekolah yang sudah dipilih calon siswa
                        disimpan sebagai snapshot pada data pendaftaran.
                        Mengubah atau menonaktifkan master tidak mengubah
                        data pendaftar yang sudah tersimpan.
                    </p>

                </div>

            </div>

        </div>

    </div>
@endsection