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

            <p class="mt-4 text-sm font-semibold text-emerald-600">
                Sistem
            </p>

            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                Profil Sekolah
            </h1>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Kelola identitas, alamat, dan informasi kontak sekolah
                yang digunakan oleh SPMB MARSA.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                NPSN
            </div>

            <div class="mt-1 text-sm font-semibold text-slate-800">
                {{ $school->npsn ?: '-' }}
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <div class="font-semibold">
                Terdapat data yang perlu diperbaiki.
            </div>

            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
        <div class="flex items-start gap-3">
            <i
                data-lucide="info"
                class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"
            ></i>

            <div>
                <h2 class="text-sm font-semibold text-blue-900">
                    Profil institusi
                </h2>

                <p class="mt-1 text-sm leading-6 text-blue-800">
                    Data di halaman ini merupakan identitas utama sekolah.
                    Kepala sekolah tetap disimpan pada masing-masing periode
                    SPMB agar histori periode sebelumnya tidak berubah.
                </p>
            </div>
        </div>
    </section>

    <form
        method="POST"
        action="{{ route('admin.school-profile.update') }}"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        {{-- Identitas --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i data-lucide="school" class="h-5 w-5"></i>
                </div>

                <div>
                    <h2 class="font-bold text-slate-900">
                        Identitas Sekolah
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Informasi dasar institusi yang digunakan dalam sistem.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label
                        for="name"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Nama Sekolah
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $school->name) }}"
                        maxlength="150"
                        required
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label
                        for="npsn"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        NPSN
                    </label>

                    <input
                        id="npsn"
                        type="text"
                        name="npsn"
                        value="{{ old('npsn', $school->npsn) }}"
                        maxlength="30"
                        inputmode="numeric"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>
            </div>
        </section>

        {{-- Alamat --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <i data-lucide="map-pin" class="h-5 w-5"></i>
                </div>

                <div>
                    <h2 class="font-bold text-slate-900">
                        Alamat Sekolah
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Alamat lengkap dan wilayah administratif sekolah.
                    </p>
                </div>
            </div>

            <div class="mt-6">
                <label
                    for="address"
                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                >
                    Alamat
                </label>

                <textarea
                    id="address"
                    name="address"
                    rows="3"
                    maxlength="2000"
                    placeholder="Jalan, nomor, RT/RW, atau keterangan alamat..."
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                >{{ old('address', $school->address) }}</textarea>
            </div>

            <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label
                        for="village"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Desa / Kelurahan
                    </label>

                    <input
                        id="village"
                        type="text"
                        name="village"
                        value="{{ old('village', $school->village) }}"
                        maxlength="100"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label
                        for="district"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Kecamatan
                    </label>

                    <input
                        id="district"
                        type="text"
                        name="district"
                        value="{{ old('district', $school->district) }}"
                        maxlength="100"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label
                        for="city"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Kabupaten / Kota
                    </label>

                    <input
                        id="city"
                        type="text"
                        name="city"
                        value="{{ old('city', $school->city) }}"
                        maxlength="100"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label
                        for="province"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Provinsi
                    </label>

                    <input
                        id="province"
                        type="text"
                        name="province"
                        value="{{ old('province', $school->province) }}"
                        maxlength="100"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label
                        for="postal_code"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Kode Pos
                    </label>

                    <input
                        id="postal_code"
                        type="text"
                        name="postal_code"
                        value="{{ old('postal_code', $school->postal_code) }}"
                        maxlength="10"
                        inputmode="numeric"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>
            </div>
        </section>

        {{-- Kontak --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i data-lucide="contact" class="h-5 w-5"></i>
                </div>

                <div>
                    <h2 class="font-bold text-slate-900">
                        Kontak & Informasi Online
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Informasi yang dapat digunakan untuk menghubungi sekolah.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label
                        for="phone"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Telepon
                    </label>

                    <input
                        id="phone"
                        type="text"
                        name="phone"
                        value="{{ old('phone', $school->phone) }}"
                        maxlength="30"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label
                        for="whatsapp"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        WhatsApp
                    </label>

                    <input
                        id="whatsapp"
                        type="text"
                        name="whatsapp"
                        value="{{ old('whatsapp', $school->whatsapp) }}"
                        maxlength="30"
                        placeholder="08xxxxxxxxxx"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label
                        for="email"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Email
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $school->email) }}"
                        maxlength="150"
                        placeholder="sekolah@example.com"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label
                        for="website"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Website
                    </label>

                    <input
                        id="website"
                        type="url"
                        name="website"
                        value="{{ old('website', $school->website) }}"
                        maxlength="150"
                        placeholder="https://..."
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >

                    <p class="mt-1.5 text-xs leading-5 text-slate-500">
                        Gunakan alamat lengkap, misalnya https://sekolah.sch.id.
                    </p>
                </div>
            </div>
        </section>

        {{-- Branding placeholder --}}
        <section class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm">
                    <i data-lucide="image" class="h-5 w-5"></i>
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-slate-900">
                            Logo & Favicon
                        </h2>

                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600">
                            Tahap berikutnya
                        </span>
                    </div>

                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Upload logo sekolah dan favicon akan ditambahkan setelah
                        Profil Sekolah Core selesai diverifikasi.
                    </p>
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a
                href="{{ route('admin.settings.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50"
            >
                Batal
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700"
            >
                <i data-lucide="save" class="h-4 w-4"></i>
                Simpan Profil Sekolah
            </button>
        </div>
    </form>
</div>
@endsection