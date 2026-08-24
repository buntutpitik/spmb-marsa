@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div>
        <p class="text-sm font-semibold text-emerald-600">Sistem</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pengaturan SPMB</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
            Kelola konfigurasi utama SPMB MARSA dari satu tempat.
            Pengaturan dapat disesuaikan untuk periode penerimaan yang berbeda.
        </p>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i data-lucide="school" class="h-5 w-5"></i>
                </div>
                <div>
                    <h2 class="font-bold text-slate-900">Profil Sekolah</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Nama sekolah, NPSN, alamat, kontak, kepala sekolah, logo, dan informasi institusi.
                    </p>
                </div>
            </div>
            <div class="mt-5">
                <span class="text-xs font-semibold text-slate-400">Akan dikembangkan</span>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i data-lucide="calendar-range" class="h-5 w-5"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-slate-900">Periode SPMB</h2>
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Aktif</span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Kelola tahun ajaran, tanggal pendaftaran, status periode,
                        biaya daftar ulang, dan konfigurasi nomor pendaftaran.
                    </p>
                </div>
            </div>
            <div class="mt-5">
                <a href="{{ route('admin.periods.index') }}"
                   class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
                    Kelola Periode SPMB
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <i data-lucide="graduation-cap" class="h-5 w-5"></i>
                </div>
                <div>
                    <h2 class="font-bold text-slate-900">Jurusan</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Kelola kompetensi keahlian dan jurusan yang tersedia pada masing-masing periode.
                    </p>
                </div>
            </div>
            <div class="mt-5"><span class="text-xs font-semibold text-slate-400">Akan dikembangkan</span></div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <i data-lucide="route" class="h-5 w-5"></i>
                </div>
                <div>
                    <h2 class="font-bold text-slate-900">Jalur Pendaftaran</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Atur jalur KHUSUS, UMUM, rentang tanggal, status aktif, dan urutan jalur.
                    </p>
                </div>
            </div>
            <div class="mt-5"><span class="text-xs font-semibold text-slate-400">Akan dikembangkan</span></div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i data-lucide="school" class="h-5 w-5"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-slate-900">Asal Sekolah</h2>
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Aktif</span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Kelola daftar SMP/MTs yang muncul pada pilihan Asal Sekolah di formulir pendaftaran.
                    </p>
                </div>
            </div>
            <div class="mt-5">
                <a href="{{ route('admin.origin-schools.index') }}"
                   class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
                    Kelola Asal Sekolah
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i data-lucide="badge-percent" class="h-5 w-5"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-slate-900">Keringanan / Prestasi</h2>
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Aktif</span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Tambah, edit, aktifkan, nonaktifkan, dan atur pilihan keringanan atau prestasi per periode.
                    </p>
                </div>
            </div>
            <div class="mt-5">
                <a href="{{ route('admin.relief-options.index') }}"
                   class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
                    Kelola Keringanan / Prestasi
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i data-lucide="sparkles" class="h-5 w-5"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-slate-900">Program Khusus</h2>
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Aktif</span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Kelola program seperti KKO dan Pondok Pesantren, termasuk ketersediaannya pada setiap periode SPMB.
                    </p>
                </div>
            </div>
            <div class="mt-5">
                <a href="{{ route('admin.special-programs.index') }}"
                   class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
                    Kelola Program Khusus
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
        <div class="flex items-start gap-3">
            <i data-lucide="info" class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"></i>
            <div>
                <h3 class="text-sm font-semibold text-blue-900">Konfigurasi berbasis periode</h3>
                <p class="mt-1 text-sm leading-6 text-blue-800">
                    Perubahan konfigurasi periode berikutnya tidak akan mengubah histori pendaftaran pada periode sebelumnya.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
