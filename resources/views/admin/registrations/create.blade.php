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


            {{-- Pilihan Pendaftaran --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">1</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Pilihan Pendaftaran</h2>
                            <p class="mt-1 text-sm text-slate-500">Periode dan jalur ditentukan otomatis oleh sistem.</p>
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

            {{-- Identitas Siswa --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">2</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Identitas Siswa</h2>
                            <p class="mt-1 text-sm text-slate-500">Masukkan identitas utama calon siswa.</p>
                        </div>
                    </div>
                </div>
                <div class="grid gap-5 p-6 md:grid-cols-2">
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
                </div>
            </section>

            {{-- Alamat Siswa --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">3</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Alamat Siswa</h2>
                            <p class="mt-1 text-sm text-slate-500">Alamat tempat tinggal calon siswa.</p>
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

            {{-- Kontak Pendaftaran --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">4</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Kontak Pendaftaran</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Nomor WhatsApp aktif untuk komunikasi pendaftaran.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div>
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
                            value="{{ old('whatsapp') }}"
                            class="{{ $inputClass }}"
                            placeholder="Contoh: 6281234567890"
                            required
                        >
                        <p class="mt-2 text-xs text-slate-500">
                            Gunakan nomor WhatsApp aktif yang dapat dihubungi.
                        </p>
                    </div>
                </div>
            </section>

            {{-- Asal Sekolah --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">5</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Asal Sekolah</h2>
                            <p class="mt-1 text-sm text-slate-500">Pilih asal sekolah dan masukkan nilai kelulusan.</p>
                        </div>
                    </div>
                </div>
                <div class="grid gap-5 p-6 md:grid-cols-2">
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

                        <div id="origin_school_other_wrapper" class="mt-4" hidden>
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
                </div>
            </section>

            {{-- Jurusan & Program Khusus --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">6</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Jurusan & Program Khusus</h2>
                            <p class="mt-1 text-sm text-slate-500">Pilih jurusan dan program khusus yang diikuti.</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-6 p-6">
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
                </div>
            </section>

            {{-- Data Orang Tua/Wali --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">7</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Data Orang Tua/Wali</h2>
                            <p class="mt-1 text-sm text-slate-500">Data ayah dan ibu.</p>
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
                </div>
            </section>

            {{-- Alamat Orang Tua/Wali --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">8</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Alamat Orang Tua/Wali</h2>
                            <p class="mt-1 text-sm text-slate-500">Centang jika alamat sama dengan alamat siswa.</p>
                        </div>
                    </div>
                </div>
                <div class="grid gap-5 p-6 md:grid-cols-2 lg:grid-cols-3">
<div class="md:col-span-2 lg:col-span-3">
    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
        <input type="hidden" name="same_parent_address" value="0">
        <input type="checkbox" id="same_parent_address" name="same_parent_address" value="1" @checked((string) old('same_parent_address', '0') === '1') class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
        Alamat orang tua/wali sama dengan alamat siswa
    </label>
</div>
<div>
    <label for="parent_hamlet" class="mb-2 block text-sm font-semibold text-slate-700">Dusun</label>
    <input id="parent_hamlet" type="text" name="parent_hamlet" value="{{ old('parent_hamlet') }}" class="{{ $inputClass }}" data-parent-address="hamlet">
</div>
<div>
    <label for="parent_rt" class="mb-2 block text-sm font-semibold text-slate-700">RT</label>
    <input id="parent_rt" type="text" name="parent_rt" value="{{ old('parent_rt') }}" maxlength="10" class="{{ $inputClass }}" data-parent-address="rt">
</div>
<div>
    <label for="parent_rw" class="mb-2 block text-sm font-semibold text-slate-700">RW</label>
    <input id="parent_rw" type="text" name="parent_rw" value="{{ old('parent_rw') }}" maxlength="10" class="{{ $inputClass }}" data-parent-address="rw">
</div>
<div>
    <label for="parent_village" class="mb-2 block text-sm font-semibold text-slate-700">Desa / Kelurahan</label>
    <input id="parent_village" type="text" name="parent_village" value="{{ old('parent_village') }}" class="{{ $inputClass }}" data-parent-address="village">
</div>
<div>
    <label for="parent_district" class="mb-2 block text-sm font-semibold text-slate-700">Kecamatan</label>
    <input id="parent_district" type="text" name="parent_district" value="{{ old('parent_district') }}" class="{{ $inputClass }}" data-parent-address="district">
</div>
<div>
    <label for="parent_city" class="mb-2 block text-sm font-semibold text-slate-700">Kabupaten / Kota</label>
    <input id="parent_city" type="text" name="parent_city" value="{{ old('parent_city') }}" class="{{ $inputClass }}" data-parent-address="city">
</div>
<div>
    <label for="parent_province" class="mb-2 block text-sm font-semibold text-slate-700">Provinsi</label>
    <input id="parent_province" type="text" name="parent_province" value="{{ old('parent_province') }}" class="{{ $inputClass }}" data-parent-address="province">
</div>
<div>
    <label for="parent_postal_code" class="mb-2 block text-sm font-semibold text-slate-700">Kode Pos</label>
    <input id="parent_postal_code" type="text" name="parent_postal_code" value="{{ old('parent_postal_code') }}" inputmode="numeric" maxlength="5" class="{{ $inputClass }}" data-parent-address="postal_code">
</div>
                </div>
            </section>

            {{-- Data Internal --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">9</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Data Internal</h2>
                            <p class="mt-1 text-sm text-slate-500">Informasi pembawa dan keringanan khusus petugas.</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-6 p-6">
<div class="grid gap-5 md:grid-cols-2"><div>
                            <label
                                for="referrer_name"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Nama Pembawa
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
                        </div><div>
    <label for="referrer_source" class="mb-2 block text-sm font-semibold text-slate-700">Asal Pembawa</label>
    <select id="referrer_source" name="referrer_source" class="{{ $inputClass }}">
        <option value="">Pilih asal pembawa</option>
        @foreach (($referralSources ?? collect()) as $source)
            <option value="{{ $source->name }}" @selected(old('referrer_source') === $source->name)>{{ $source->name }}</option>
        @endforeach
        @if (old('referrer_source') && !($referralSources ?? collect())->contains('name', old('referrer_source')))
            <option value="{{ old('referrer_source') }}" selected>{{ old('referrer_source') }}</option>
        @endif
    </select>
</div></div>
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
                </div>
            </section>

            {{-- Catatan --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">10</div>
                        <div>
                            <h2 class="font-bold text-slate-900">Catatan</h2>
                            <p class="mt-1 text-sm text-slate-500">Informasi tambahan bila diperlukan.</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
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



<script>
document.addEventListener('DOMContentLoaded', () => {
    const originSchool = document.getElementById('origin_school_id');
    const otherWrapper = document.getElementById('origin_school_other_wrapper');
    const otherInput = document.getElementById('origin_school_other');

    if (originSchool && otherWrapper && otherInput) {
        const syncOtherSchool = () => {
            const isOther = originSchool.value === 'OTHER';

            otherWrapper.hidden = !isOther;
            otherInput.required = isOther;
            otherInput.disabled = !isOther;

            if (!isOther) {
                otherInput.value = '';
            }
        };

        originSchool.addEventListener('change', syncOtherSchool);
        syncOtherSchool();
    }
    const checkbox = document.getElementById('same_parent_address');
    if (!checkbox) return;
    const fields = ['hamlet', 'rt', 'rw', 'village', 'district', 'city', 'province', 'postal_code'];
    const sync = () => {
        if (!checkbox.checked) return;
        fields.forEach(field => {
            const student = document.getElementById(field);
            const parent = document.getElementById('parent_' + field);
            if (student && parent) parent.value = student.value;
        });
    };
    checkbox.addEventListener('change', sync);
    fields.forEach(field => document.getElementById(field)?.addEventListener('input', sync));
    sync();
});
</script>


@endsection
