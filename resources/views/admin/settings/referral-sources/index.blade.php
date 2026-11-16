@extends('layouts.app')

@section('content')
<div class="space-y-8">

    <div>
        <a href="{{ route('admin.settings.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Pengaturan
        </a>

        <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Master Asal Pembawa
        </h1>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Kelola pilihan Asal Pembawa untuk formulir pendaftaran
            yang diisi oleh petugas.
        </p>
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

    {{-- Tambah master --}}
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h2 class="font-bold text-slate-900">
                Tambah Asal Pembawa
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Data baru akan langsung aktif dan tersedia
                pada pilihan Asal Pembawa.
            </p>
        </div>

        <form method="POST"
              action="{{ route('admin.referral-sources.store') }}"
              class="grid gap-5 p-6 lg:grid-cols-[minmax(0,1fr)_auto]">
            @csrf

            <div>
                <label for="new-referral-name"
                       class="mb-2 block text-sm font-semibold text-slate-700">
                    Nama Asal Pembawa
                </label>

                <input id="new-referral-name"
                       type="text"
                       name="name"
                       value="{{ old('name') }}"
                       maxlength="150"
                       required
                       placeholder="Contoh: ALUMNI"
                       class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm uppercase focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
            </div>

            <div class="flex items-end">
                <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Tambah
                </button>
            </div>
        </form>
    </section>

    {{-- Daftar master --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h2 class="font-bold text-slate-900">
                Daftar Asal Pembawa
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                {{ $referralSources->count() }} data pada master.
            </p>
        </div>

        @if ($referralSources->isEmpty())
            <div class="p-8 text-center text-sm text-slate-500">
                Belum ada data asal pembawa.
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($referralSources as $source)
                    <div class="p-6">

                        <form method="POST"
                              action="{{ route('admin.referral-sources.update', $source) }}"
                              class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="referral-name-{{ $source->id }}"
                                       class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Nama Asal Pembawa
                                </label>

                                <input id="referral-name-{{ $source->id }}"
                                       type="text"
                                       name="name"
                                       value="{{ $source->name }}"
                                       maxlength="150"
                                       required
                                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium uppercase text-slate-800 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $source->is_active
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-slate-100 text-slate-500' }}">
                                    {{ $source->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>

                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>

                        <form method="POST"
                              action="{{ route('admin.referral-sources.toggle', $source) }}"
                              class="mt-3">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-semibold transition
                                    {{ $source->is_active
                                        ? 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                        : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                <i data-lucide="{{ $source->is_active ? 'power-off' : 'power' }}"
                                   class="h-4 w-4"></i>

                                {{ $source->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>

                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Informasi historis --}}
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
        <div class="flex items-start gap-3">
            <i data-lucide="info"
               class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"></i>

            <div>
                <h3 class="text-sm font-semibold text-blue-900">
                    Data historis tetap aman
                </h3>

                <p class="mt-1 text-sm leading-6 text-blue-800">
                    Asal Pembawa disimpan sebagai teks pada data
                    pendaftaran. Mengubah nama atau menonaktifkan
                    master tidak mengubah data pendaftar yang
                    sudah tersimpan.
                </p>
            </div>
        </div>
    </div>

</div>
@endsection
