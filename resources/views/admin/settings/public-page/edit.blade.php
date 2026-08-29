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
                Tampilan Publik
            </p>

            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                Halaman Publik
            </h1>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Kelola konten informasi yang ditampilkan pada halaman utama
                SPMB tanpa mengubah struktur dan desain halaman.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                Sekolah
            </div>

            <div class="mt-1 text-sm font-semibold text-slate-800">
                {{ $school->name }}
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
                    Konten halaman utama
                </h2>

                <p class="mt-1 text-sm leading-6 text-blue-800">
                    Nama sekolah, logo, kontak, periode aktif, jurusan,
                    jalur pendaftaran, tanggal pendaftaran, dan biaya
                    daftar ulang tetap mengambil data master sistem.
                    Halaman ini hanya mengatur konten editorial dan
                    bagian informasi yang ingin ditampilkan.
                </p>
            </div>
        </div>
    </section>

    <form
        method="POST"
        action="{{ route('admin.public-page.update') }}"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        {{-- Hero --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i data-lucide="panel-top" class="h-5 w-5"></i>
                </div>

                <div>
                    <h2 class="font-bold text-slate-900">
                        Hero Halaman Utama
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Atur judul dan pengantar utama yang pertama kali
                        dilihat calon siswa.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-5">
                <div>
                    <label
                        for="hero_title"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Judul Utama
                    </label>

                    <input
                        id="hero_title"
                        type="text"
                        name="hero_title"
                        value="{{ old('hero_title', $setting->hero_title) }}"
                        maxlength="200"
                        placeholder="Contoh: SPMB SMK Ma'arif 9 Kebumen"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label
                        for="hero_subtitle"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Subjudul
                    </label>

                    <input
                        id="hero_subtitle"
                        type="text"
                        name="hero_subtitle"
                        value="{{ old('hero_subtitle', $setting->hero_subtitle) }}"
                        maxlength="255"
                        placeholder="Contoh: Penerimaan Murid Baru Tahun Ajaran 2027/2028"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label
                        for="hero_description"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Deskripsi
                    </label>

                    <textarea
                        id="hero_description"
                        name="hero_description"
                        rows="4"
                        maxlength="2000"
                        placeholder="Tuliskan pengantar singkat untuk calon siswa..."
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >{{ old('hero_description', $setting->hero_description) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Pengumuman --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <i data-lucide="megaphone" class="h-5 w-5"></i>
                    </div>

                    <div>
                        <h2 class="font-bold text-slate-900">
                            Pengumuman Terbaru
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Informasi penting yang ditampilkan pada halaman utama.
                        </p>
                    </div>
                </div>

                <label class="flex shrink-0 cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        name="show_announcement"
                        value="1"
                        @checked(old('show_announcement', $setting->show_announcement))
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                    >

                    <span class="text-sm font-semibold text-slate-600">
                        Tampilkan
                    </span>
                </label>
            </div>

            <div class="mt-6 grid gap-5">
                <div>
                    <label
                        for="announcement_title"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Judul Pengumuman
                    </label>

                    <input
                        id="announcement_title"
                        type="text"
                        name="announcement_title"
                        value="{{ old('announcement_title', $setting->announcement_title) }}"
                        maxlength="200"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label
                        for="announcement_body"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        Isi Pengumuman
                    </label>

                    <textarea
                        id="announcement_body"
                        name="announcement_body"
                        rows="5"
                        maxlength="5000"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    >{{ old('announcement_body', $setting->announcement_body) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Persyaratan --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <i data-lucide="clipboard-check" class="h-5 w-5"></i>
                    </div>

                    <div>
                        <h2 class="font-bold text-slate-900">
                            Informasi Pendaftaran
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Tuliskan persyaratan atau dokumen yang perlu
                            disiapkan calon siswa.
                        </p>
                    </div>
                </div>

                <label class="flex shrink-0 cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        name="show_requirements"
                        value="1"
                        @checked(old('show_requirements', $setting->show_requirements))
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                    >

                    <span class="text-sm font-semibold text-slate-600">
                        Tampilkan
                    </span>
                </label>
            </div>

            <div class="mt-6">
                <textarea
                    id="requirements"
                    name="requirements"
                    rows="7"
                    maxlength="10000"
                    placeholder="Satu persyaratan per baris..."
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                >{{ old('requirements', $setting->requirements) }}</textarea>

                <p class="mt-2 text-xs leading-5 text-slate-500">
                    Gunakan satu baris untuk setiap poin agar mudah
                    ditampilkan sebagai daftar pada halaman publik.
                </p>
            </div>
        </section>

        {{-- Cara Mendaftar --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        <i data-lucide="list-ordered" class="h-5 w-5"></i>
                    </div>

                    <div>
                        <h2 class="font-bold text-slate-900">
                            Cara Mendaftar
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Langkah pendaftaran yang nantinya ditampilkan
                            sebagai alur sederhana pada halaman publik.
                        </p>
                    </div>
                </div>

                <label class="flex shrink-0 cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        name="show_registration_steps"
                        value="1"
                        @checked(old('show_registration_steps', $setting->show_registration_steps))
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                    >

                    <span class="text-sm font-semibold text-slate-600">
                        Tampilkan
                    </span>
                </label>
            </div>

            <div class="mt-6">
                <textarea
                    id="registration_steps"
                    name="registration_steps"
                    rows="7"
                    maxlength="10000"
                    placeholder="Isi formulir pendaftaran&#10;Periksa kembali data&#10;Kirim pendaftaran&#10;Simpan atau cetak kartu"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                >{{ old('registration_steps', $setting->registration_steps) }}</textarea>

                <p class="mt-2 text-xs leading-5 text-slate-500">
                    Satu langkah per baris. Urutan baris menjadi urutan
                    langkah pada halaman publik.
                </p>
            </div>
        </section>

        {{-- Daftar Ulang --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                        <i data-lucide="wallet-cards" class="h-5 w-5"></i>
                    </div>

                    <div>
                        <h2 class="font-bold text-slate-900">
                            Informasi Daftar Ulang
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Informasi tambahan mengenai proses daftar ulang.
                            Nominal biaya tetap mengambil konfigurasi periode.
                        </p>
                    </div>
                </div>

                <label class="flex shrink-0 cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        name="show_reenrollment_information"
                        value="1"
                        @checked(old('show_reenrollment_information', $setting->show_reenrollment_information))
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                    >

                    <span class="text-sm font-semibold text-slate-600">
                        Tampilkan
                    </span>
                </label>
            </div>

            <div class="mt-6">
                <textarea
                    id="reenrollment_information"
                    name="reenrollment_information"
                    rows="6"
                    maxlength="10000"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                >{{ old('reenrollment_information', $setting->reenrollment_information) }}</textarea>
            </div>
        </section>

        {{-- Kontak --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600">
                        <i data-lucide="contact-round" class="h-5 w-5"></i>
                    </div>

                    <div>
                        <h2 class="font-bold text-slate-900">
                            Kontak Panitia
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Nomor telepon, WhatsApp, email, alamat, dan website
                            mengambil data dari Profil Sekolah.
                        </p>
                    </div>
                </div>

                <label class="flex shrink-0 cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        name="show_contact"
                        value="1"
                        @checked(old('show_contact', $setting->show_contact))
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                    >

                    <span class="text-sm font-semibold text-slate-600">
                        Tampilkan
                    </span>
                </label>
            </div>

            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm leading-6 text-slate-600">
                    Untuk mengubah informasi kontak, gunakan menu
                    <a
                        href="{{ route('admin.school-profile.edit') }}"
                        class="font-semibold text-emerald-600 hover:text-emerald-700"
                    >
                        Profil Sekolah
                    </a>.
                </p>
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
                Simpan Halaman Publik
            </button>
        </div>
    </form>
</div>
@endsection