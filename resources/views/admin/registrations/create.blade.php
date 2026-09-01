@extends('layouts.app')

@section('content')
    @php
        $inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100';

        $selectedReliefOptions = collect(old('relief_options', []))->map(fn ($id) => (int) $id)->all();

        $selectedSpecialPrograms = collect(old('special_programs', []))->map(fn ($id) => (int) $id)->all();
    @endphp

    <div class="space-y-8">

        {{-- Heading --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a
                    href="{{ route('admin.registrations.index', ['period_id' => $selectedPeriod->id]) }}"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 hover:text-emerald-700"
                >
                    <i
                        data-lucide="arrow-left"
                        class="h-4 w-4"
                    ></i>

                    Daftar Pendaftar
                </a>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Tambah Pendaftar
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    Dibuat otomatis oleh sistem
                    &middot;
                    {{ $selectedPeriod->name }}
                </p>
            </div>

            <div class="inline-flex self-start items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 lg:self-auto">
                <i
                    data-lucide="pencil"
                    class="h-4 w-4"
                ></i>

                Input Admin
            </div>
        </div>

        {{-- Information --}}
        <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                    <i
                        data-lucide="info"
                        class="h-5 w-5"
                    ></i>
                </div>

                <div>
                    <div class="font-bold text-blue-900">
                        Input Pendaftar oleh Petugas
                    </div>

                    <p class="mt-1 text-sm leading-6 text-blue-800">
                        Gunakan halaman ini untuk menambahkan calon siswa melalui
                        petugas. Nomor pendaftaran, status,
                        sumber data, token publik, dan waktu pendaftaran dibuat
                        otomatis oleh sistem.
                    </p>
                </div>
            </div>
        </div>

        {{-- Validation --}}
        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <div class="flex gap-3">
                    <div class="mt-0.5 text-red-600">
                        <i
                            data-lucide="circle-alert"
                            class="h-5 w-5"
                        ></i>
                    </div>

                    <div>
                        <h2 class="font-semibold text-red-800">
                            Periksa kembali data yang dimasukkan.
                        </h2>

                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('admin.registrations.store') }}"
            class="space-y-6"
        >
            @csrf


            {{-- Identitas --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">
                            1
                        </div>

                        <div>
                            <h2 class="font-bold text-slate-900">
                                Identitas Calon Siswa
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Masukkan identitas utama calon siswa.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 p-6 md:grid-cols-2">
                    <div>
                        <label
                            for="nik"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            NIK
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="nik"
                            type="text"
                            name="nik"
                            inputmode="numeric"
                            maxlength="16"
                            value="{{ old('nik') }}"
                            class="{{ $inputClass }}"
                            required
                        >
                    </div>

                    <div>
                        <label
                            for="nisn"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            NISN
                        </label>

                        <input
                            id="nisn"
                            type="text"
                            name="nisn"
                            inputmode="numeric"
                            maxlength="20"
                            value="{{ old('nisn') }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label
                            for="full_name"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Nama Lengkap
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="full_name"
                            type="text"
                            name="full_name"
                            maxlength="150"
                            value="{{ old('full_name') }}"
                            class="{{ $inputClass }}"
                            required
                        >
                    </div>

                    <div>
                        <label
                            for="birth_place"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Tempat Lahir
                        </label>

                        <input
                            id="birth_place"
                            type="text"
                            name="birth_place"
                            maxlength="100"
                            value="{{ old('birth_place') }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="birth_date"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Tanggal Lahir
                        </label>

                        <input
                            id="birth_date"
                            type="date"
                            name="birth_date"
                            value="{{ old('birth_date') }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="gender"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Jenis Kelamin
                        </label>

                        <select
                            id="gender"
                            name="gender"
                            class="{{ $inputClass }}"
                        >
                            <option value="">Pilih jenis kelamin</option>

                            <option
                                value="L"
                                @selected(old('gender') === 'L')
                            >
                                Laki-laki
                            </option>

                            <option
                                value="P"
                                @selected(old('gender') === 'P')
                            >
                                Perempuan
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="religion"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Agama
                        </label>

                        <input
                            id="religion"
                            type="text"
                            name="religion"
                            maxlength="50"
                            value="{{ old('religion') }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label
                            for="origin_school_id"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Asal Sekolah
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="origin_school_id"
                            name="origin_school_id"
                            class="{{ $inputClass }}"
                            required
                        >
                            <option value="">Pilih asal sekolah</option>

                            @foreach ($originSchools as $school)
                                <option
                                    value="{{ $school->id }}"
                                    @selected(
                                        (string) old('origin_school_id') ===
                                        (string) $school->id
                                    )
                                >
                                    {{ $school->name }}
                                </option>
                            @endforeach

                            <option
                                value="OTHER"
                                @selected(old('origin_school_id') === 'OTHER')
                            >
                                Lainnya / Belum Ada di Master
                            </option>
                        </select>

                        <div class="mt-4">
                            <label
                                for="origin_school_other"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Nama Sekolah Lainnya
                            </label>

                            <input
                                id="origin_school_other"
                                type="text"
                                name="origin_school_other"
                                maxlength="150"
                                value="{{ old('origin_school_other') }}"
                                class="{{ $inputClass }}"
                                placeholder="Isi jika memilih Lainnya"
                            >
                        </div>

                        <p class="mt-2 text-xs leading-5 text-slate-400">
                            Pilih sekolah dari master. Jika belum tersedia,
                            pilih Lainnya lalu isi nama sekolah.
                        </p>
                    </div>
                </div>
            </section>

            {{-- Informasi Pendaftaran --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">
                            2
                        </div>

                        <div>
                            <h2 class="font-bold text-slate-900">
                                Informasi Pendaftaran
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Pilih jurusan calon siswa. Jalur pendaftaran ditentukan otomatis oleh sistem berdasarkan jadwal.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 p-6 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Periode
                        </label>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                            {{ $selectedPeriod->name }}
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Nomor Pendaftaran
                        </label>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                            Dibuat otomatis oleh sistem
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Jalur Pendaftaran
                        </label>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                            Ditentukan otomatis oleh sistem
                        </div>

                        <p class="mt-2 text-xs leading-5 text-slate-400">
                            Jalur pendaftaran ditentukan otomatis oleh sistem berdasarkan jadwal.
                        </p>
                    </div>

                    <div>
                        <label
                            for="major_id"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Jurusan
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="major_id"
                            name="major_id"
                            class="{{ $inputClass }}"
                            required
                        >
                            <option value="">Pilih jurusan</option>

                            @foreach ($majors as $major)
                                <option
                                    value="{{ $major->id }}"
                                    @selected(
                                        (int) old(
                                            'major_id',
                                            null
                                        ) === (int) $major->id
                                    )
                                >
                                    {{ $major->code }} - {{ $major->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="graduation_score"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Nilai Kelulusan
                        </label>

                        <input
                            id="graduation_score"
                            type="number"
                            name="graduation_score"
                            min="0"
                            max="100"
                            step="0.01"
                            value="{{ old(
                                'graduation_score',
                                null
                            ) }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Status
                        </label>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                            {{ match ('REGISTERED') {
                                'REGISTERED' => 'Terdaftar',
                                'ACCEPTED' => 'Diterima',
                                'REJECTED' => 'Ditolak',
                                'REENROLLED' => 'Daftar Ulang',
                                'WITHDRAWN' => 'Mengundurkan Diri',
                                default => 'REGISTERED',
                            } }}
                        </div>

                        <p class="mt-2 text-xs text-slate-400">
                            Status dikelola melalui alur perubahan status.
                        </p>
                    </div>
                </div>
            </section>

            {{-- Alamat --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">
                            3
                        </div>

                        <div>
                            <h2 class="font-bold text-slate-900">
                                Alamat
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Masukkan alamat tempat tinggal calon siswa.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 p-6 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label
                            for="hamlet"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Dusun
                        </label>

                        <input
                            id="hamlet"
                            type="text"
                            name="hamlet"
                            value="{{ old('hamlet') }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="rt"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            RT
                        </label>

                        <input
                            id="rt"
                            type="text"
                            name="rt"
                            maxlength="10"
                            value="{{ old('rt') }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="rw"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            RW
                        </label>

                        <input
                            id="rw"
                            type="text"
                            name="rw"
                            maxlength="10"
                            value="{{ old('rw') }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="village"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Desa / Kelurahan
                        </label>

                        <input
                            id="village"
                            type="text"
                            name="village"
                            value="{{ old('village') }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="district"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Kecamatan
                        </label>

                        <input
                            id="district"
                            type="text"
                            name="district"
                            value="{{ old('district') }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="city"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Kabupaten / Kota
                        </label>

                        <input
                            id="city"
                            type="text"
                            name="city"
                            value="{{ old('city') }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="province"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Provinsi
                        </label>

                        <input
                            id="province"
                            type="text"
                            name="province"
                            value="{{ old(
                                'province',
                                null
                            ) }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="postal_code"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Kode Pos
                        </label>

                        <input
                            id="postal_code"
                            type="text"
                            name="postal_code"
                            inputmode="numeric"
                            maxlength="5"
                            value="{{ old(
                                'postal_code',
                                null
                            ) }}"
                            class="{{ $inputClass }}"
                        >
                    </div>
                </div>
            </section>

            {{-- Orang Tua --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">
                            4
                        </div>

                        <div>
                            <h2 class="font-bold text-slate-900">
                                Orang Tua & Kontak
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Data orang tua dan nomor WhatsApp aktif.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 p-6 md:grid-cols-2">
                    <div>
                        <label
                            for="father_name"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Nama Ayah
                        </label>

                        <input
                            id="father_name"
                            type="text"
                            name="father_name"
                            value="{{ old(
                                'father_name',
                                null
                            ) }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="father_job"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Pekerjaan Ayah
                        </label>

                        <input
                            id="father_job"
                            type="text"
                            name="father_job"
                            value="{{ old(
                                'father_job',
                                null
                            ) }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="mother_name"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Nama Ibu
                        </label>

                        <input
                            id="mother_name"
                            type="text"
                            name="mother_name"
                            value="{{ old(
                                'mother_name',
                                null
                            ) }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div>
                        <label
                            for="mother_job"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Pekerjaan Ibu
                        </label>

                        <input
                            id="mother_job"
                            type="text"
                            name="mother_job"
                            value="{{ old(
                                'mother_job',
                                null
                            ) }}"
                            class="{{ $inputClass }}"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label
                            for="whatsapp"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Nomor WhatsApp
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="whatsapp"
                            type="text"
                            name="whatsapp"
                            maxlength="30"
                            value="{{ old(
                                'whatsapp',
                                null
                            ) }}"
                            class="{{ $inputClass }}"
                            required
                        >
                    </div>
                </div>
            </section>

            {{-- Internal --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">
                            5
                        </div>

                        <div>
                            <h2 class="font-bold text-slate-900">
                                Data Internal & Program
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Keringanan dan referral dikelola oleh petugas.
                                Program Khusus dapat disesuaikan bila diperlukan.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-7 p-6">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label
                                for="referrer_name"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Nama Pembawa / Referral
                            </label>

                            <input
                                id="referrer_name"
                                type="text"
                                name="referrer_name"
                                maxlength="150"
                                value="{{ old(
                                    'referrer_name',
                                    null
                                ) }}"
                                class="{{ $inputClass }}"
                            >
                        </div>

                        <div>
                            <label
                                for="referrer_source"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Asal Referral
                            </label>

                            <input
                                id="referrer_source"
                                type="text"
                                name="referrer_source"
                                maxlength="150"
                                value="{{ old(
                                    'referrer_source',
                                    null
                                ) }}"
                                class="{{ $inputClass }}"
                            >
                        </div>
                    </div>

                    <div>
                        <div class="mb-3">
                            <h3 class="text-sm font-bold text-slate-800">
                                Keringanan / Prestasi
                            </h3>

                            <p class="mt-1 text-xs leading-5 text-slate-400">
                                Pilih keringanan atau prestasi yang berlaku
                                untuk pendaftar ini.
                            </p>
                        </div>

                        @if ($reliefOptions->isEmpty())
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                                Belum ada pilihan Keringanan / Prestasi aktif
                                pada periode ini.
                            </div>
                        @else
                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($reliefOptions as $option)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 px-4 py-3 transition hover:border-emerald-300 hover:bg-emerald-50/40">
                                        <input
                                            type="checkbox"
                                            name="relief_options[]"
                                            value="{{ $option->id }}"
                                            @checked(
                                                in_array(
                                                    (int) $option->id,
                                                    $selectedReliefOptions,
                                                    true
                                                )
                                            )
                                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        >

                                        <span>
                                            <span class="block text-sm font-semibold text-slate-800">
                                                {{ $option->name }}
                                            </span>

                                            @if ($option->description)
                                                <span class="mt-1 block text-xs leading-5 text-slate-500">
                                                    {{ $option->description }}
                                                </span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="mb-3">
                            <h3 class="text-sm font-bold text-slate-800">
                                Program Khusus
                            </h3>

                            <p class="mt-1 text-xs leading-5 text-slate-400">
                                Program Khusus yang diikuti calon siswa.
                            </p>
                        </div>

                        @if ($specialPrograms->isEmpty())
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                                Belum ada Program Khusus aktif pada periode ini.
                            </div>
                        @else
                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($specialPrograms as $program)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 px-4 py-3 transition hover:border-violet-300 hover:bg-violet-50/40">
                                        <input
                                            type="checkbox"
                                            name="special_programs[]"
                                            value="{{ $program->id }}"
                                            @checked(
                                                in_array(
                                                    (int) $program->id,
                                                    $selectedSpecialPrograms,
                                                    true
                                                )
                                            )
                                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                        >

                                        <span>
                                            <span class="block text-sm font-semibold text-slate-800">
                                                {{ $program->name }}
                                            </span>

                                            @if ($program->description)
                                                <span class="mt-1 block text-xs leading-5 text-slate-500">
                                                    {{ $program->description }}
                                                </span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div>
                        <label
                            for="notes"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Catatan
                        </label>

                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            maxlength="2000"
                            class="{{ $inputClass }} resize-none"
                            placeholder="Catatan internal bila diperlukan..."
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>
            </section>

            {{-- Actions --}}
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-end">
                <a
                    href="{{ route('admin.registrations.index', ['period_id' => $selectedPeriod->id]) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                >
                    <i
                        data-lucide="x"
                        class="h-4 w-4"
                    ></i>

                    Batal
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                >
                    <i
                        data-lucide="save"
                        class="h-4 w-4"
                    ></i>

                    Simpan Pendaftar
                </button>
            </div>
        </form>
    </div>
@endsection
