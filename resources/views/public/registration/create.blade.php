<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pendaftaran SPMB | {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800">

    <div class="min-h-screen">

        {{-- Header --}}
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">
                        SPMB Online
                    </p>

                    <h1 class="mt-1 text-xl font-bold text-slate-900">
                        {{ config('app.name') }}
                    </h1>
                </div>

                @if ($period)
                    <div class="hidden text-right sm:block">
                        <p class="text-xs text-slate-500">
                            Periode Aktif
                        </p>

                        <p class="font-semibold text-slate-900">
                            {{ $period->name }}
                        </p>
                    </div>
                @endif
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

            <div class="mb-8 max-w-3xl">
                <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    Pendaftaran Siswa Baru
                </span>

                <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    Formulir Pendaftaran
                </h2>

                <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">
                    Lengkapi data calon siswa dengan benar. Jalur pendaftaran akan
                    ditentukan otomatis oleh sistem berdasarkan tanggal pendaftaran.
                </p>
            </div>

            @if (! $period)

                <div class="mx-auto max-w-3xl rounded-2xl border border-amber-200 bg-white p-8 shadow-sm">
                    <div class="flex flex-col items-center text-center">

                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-50">
                            <svg
                                class="h-7 w-7 text-amber-600"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 8v4"></path>
                                <path d="M12 16h.01"></path>
                            </svg>
                        </div>

                        <h3 class="mt-5 text-xl font-bold text-slate-900">
                            Pendaftaran Belum Dibuka
                        </h3>

                        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-600">
                            Saat ini belum ada periode SPMB yang aktif dan berstatus dibuka.
                            Silakan kembali lagi setelah periode pendaftaran diaktifkan oleh panitia.
                        </p>
                    </div>
                </div>

            @elseif (! $activePath)

                <div class="mx-auto max-w-3xl rounded-2xl border border-amber-200 bg-white p-8 shadow-sm">
                    <div class="flex flex-col items-center text-center">

                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-50">
                            <svg
                                class="h-7 w-7 text-amber-600"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 8v4"></path>
                                <path d="M12 16h.01"></path>
                            </svg>
                        </div>

                        <h3 class="mt-5 text-xl font-bold text-slate-900">
                            Belum Ada Jalur Aktif
                        </h3>

                        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-600">
                            Periode SPMB {{ $period->name }} sudah aktif, tetapi belum ada
                            jalur pendaftaran yang berlaku untuk tanggal hari ini.
                        </p>
                    </div>
                </div>

            @else

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5">
                        <div class="flex gap-3">

                            <div class="mt-0.5 text-red-600">
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 8v4"></path>
                                    <path d="M12 16h.01"></path>
                                </svg>
                            </div>

                            <div>
                                <h3 class="font-semibold text-red-800">
                                    Periksa kembali data yang Anda masukkan.
                                </h3>

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
                    action="{{ route('registration.store') }}"
                    class="space-y-6"
                    x-data="{
                        originSchool: @js((string) old('origin_school_id', ''))
                    }"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="period_id"
                        value="{{ old('period_id', $period->id) }}"
                    >

                    {{-- Section 1 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-100 px-6 py-5">
                            <div class="flex items-center gap-3">

                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">
                                    1
                                </div>

                                <div>
                                    <h3 class="font-bold text-slate-900">
                                        Pilihan Pendaftaran
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Periode dan jalur ditentukan otomatis oleh sistem.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 p-6 md:grid-cols-2">

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Periode SPMB
                                </label>

                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">
                                    {{ $period->name }}
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Jalur Aktif
                                </label>

                                <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">

                                    <p class="text-sm font-semibold text-blue-800">
                                        {{ $activePath->name }}
                                    </p>

                                    <p class="mt-1 text-xs text-blue-600">
                                        Ditentukan otomatis berdasarkan tanggal pendaftaran.
                                    </p>
                                </div>
                            </div>

                            <div class="md:col-span-2">

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
                                    required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                                    <option value="">
                                        Pilih jurusan
                                    </option>

                                    @foreach ($majors as $major)
                                        <option
                                            value="{{ $major->id }}"
                                            @selected(old('major_id') == $major->id)
                                        >
                                            {{ $major->code }} — {{ $major->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('major_id')
                                    <p class="mt-1 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>
                        </div>
                    </section>

                    {{-- Section 2 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-100 px-6 py-5">
                            <div class="flex items-center gap-3">

                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">
                                    2
                                </div>

                                <div>
                                    <h3 class="font-bold text-slate-900">
                                        Identitas Calon Siswa
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Masukkan identitas sesuai dokumen resmi.
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
                                    value="{{ old('nik') }}"
                                    inputmode="numeric"
                                    maxlength="16"
                                    required
                                    placeholder="16 digit NIK"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >

                                @error('nik')
                                    <p class="mt-1 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
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
                                    value="{{ old('nisn') }}"
                                    inputmode="numeric"
                                    placeholder="NISN jika tersedia"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >

                                @error('nisn')
                                    <p class="mt-1 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
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
                                    value="{{ old('full_name') }}"
                                    required
                                    placeholder="Nama lengkap calon siswa"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >

                                @error('full_name')
                                    <p class="mt-1 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
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
                                    value="{{ old('birth_place') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
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
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
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
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                                    <option value="">
                                        Pilih jenis kelamin
                                    </option>

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
                                    value="{{ old('religion') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            {{-- Asal Sekolah --}}
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
                                    x-model="originSchool"
                                    required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                                    <option value="">
                                        Pilih asal sekolah
                                    </option>

                                    @foreach ($originSchools as $school)
                                        <option
                                            value="{{ $school->id }}"
                                            @selected(
                                                (string) old('origin_school_id')
                                                === (string) $school->id
                                            )
                                        >
                                            {{ $school->name }}
                                            @if ($school->type)
                                                — {{ $school->type }}
                                            @endif
                                        </option>
                                    @endforeach

                                    <option
                                        value="OTHER"
                                        @selected(
                                            old('origin_school_id') === 'OTHER'
                                        )
                                    >
                                        Lainnya
                                    </option>
                                </select>

                                <p class="mt-1.5 text-xs text-slate-500">
                                    Pilih sekolah asal dari daftar. Jika tidak tersedia,
                                    pilih Lainnya.
                                </p>

                                @error('origin_school_id')
                                    <p class="mt-1.5 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>

                            {{-- Asal Sekolah Manual --}}
                            <div
                                x-show="originSchool === 'OTHER'"
                                x-cloak
                                class="md:col-span-2"
                            >
                                <label
                                    for="origin_school_other"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Nama Sekolah Asal
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="origin_school_other"
                                    type="text"
                                    name="origin_school_other"
                                    value="{{ old('origin_school_other') }}"
                                    maxlength="150"
                                    placeholder="Ketik nama SMP/MTs asal"
                                    :required="originSchool === 'OTHER'"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >

                                <p class="mt-1.5 text-xs text-slate-500">
                                    Isi hanya jika sekolah asal tidak terdapat pada daftar.
                                </p>

                                @error('origin_school_other')
                                    <p class="mt-1.5 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                        </div>
                    </section>

                    {{-- Section 3 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-100 px-6 py-5">
                            <div class="flex items-center gap-3">

                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">
                                    3
                                </div>

                                <div>
                                    <h3 class="font-bold text-slate-900">
                                        Alamat
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Masukkan alamat tempat tinggal calon siswa.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-3">

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Dusun
                                </label>

                                <input
                                    type="text"
                                    name="hamlet"
                                    value="{{ old('hamlet') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    RT
                                </label>

                                <input
                                    type="text"
                                    name="rt"
                                    value="{{ old('rt') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    RW
                                </label>

                                <input
                                    type="text"
                                    name="rw"
                                    value="{{ old('rw') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Desa / Kelurahan
                                </label>

                                <input
                                    type="text"
                                    name="village"
                                    value="{{ old('village') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Kecamatan
                                </label>

                                <input
                                    type="text"
                                    name="district"
                                    value="{{ old('district') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Kabupaten / Kota
                                </label>

                                <input
                                    type="text"
                                    name="city"
                                    value="{{ old('city') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Provinsi
                                </label>

                                <input
                                    type="text"
                                    name="province"
                                    value="{{ old('province') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Kode Pos
                                </label>

                                <input
                                    type="text"
                                    name="postal_code"
                                    value="{{ old('postal_code') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                        </div>
                    </section>

                    {{-- Section 4 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-100 px-6 py-5">
                            <div class="flex items-center gap-3">

                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">
                                    4
                                </div>

                                <div>
                                    <h3 class="font-bold text-slate-900">
                                        Orang Tua & Kontak
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Data orang tua dan nomor WhatsApp aktif.
                                    </p>
                                </div>

                            </div>
                        </div>

                        <div class="grid gap-5 p-6 md:grid-cols-2">

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Nama Ayah
                                </label>

                                <input
                                    type="text"
                                    name="father_name"
                                    value="{{ old('father_name') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Pekerjaan Ayah
                                </label>

                                <input
                                    type="text"
                                    name="father_job"
                                    value="{{ old('father_job') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Nama Ibu
                                </label>

                                <input
                                    type="text"
                                    name="mother_name"
                                    value="{{ old('mother_name') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Pekerjaan Ibu
                                </label>

                                <input
                                    type="text"
                                    name="mother_job"
                                    value="{{ old('mother_job') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
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
                                    value="{{ old('whatsapp') }}"
                                    required
                                    placeholder="Contoh: 081234567890"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >

                                <p class="mt-1 text-xs text-slate-500">
                                    Gunakan nomor WhatsApp aktif yang dapat dihubungi panitia.
                                </p>

                                @error('whatsapp')
                                    <p class="mt-1 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                        </div>
                    </section>

                                        {{-- Section 5 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-100 px-6 py-5">

                            <div class="flex items-center gap-3">

                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">
                                    5
                                </div>

                                <div>
                                    <h3 class="font-bold text-slate-900">
                                        Informasi Tambahan
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Informasi tambahan dan program khusus.
                                    </p>
                                </div>

                            </div>
                        </div>

                        <div class="grid gap-6 p-6 md:grid-cols-2">

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Nilai Kelulusan
                                </label>

                                <input
                                    type="number"
                                    name="graduation_score"
                                    value="{{ old('graduation_score') }}"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            {{-- Program Khusus --}}
                            <div class="md:col-span-2">

                                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">

                                    <div class="mb-4">

                                        <h4 class="font-semibold text-slate-900">
                                            Program Khusus
                                        </h4>

                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            Pilih program khusus yang diminati. Anda boleh memilih lebih dari satu.
                                        </p>

                                    </div>

                                    @if ($specialPrograms->isNotEmpty())

                                        <div class="grid gap-3 md:grid-cols-2">

                                            @foreach ($specialPrograms as $program)

                                                <label
                                                    for="special-program-{{ $program->id }}"
                                                    class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-blue-300 hover:bg-blue-50/40"
                                                >

                                                    <input
                                                        id="special-program-{{ $program->id }}"
                                                        type="checkbox"
                                                        name="special_programs[]"
                                                        value="{{ $program->id }}"
                                                        @checked(
                                                            in_array(
                                                                $program->id,
                                                                array_map(
                                                                    'intval',
                                                                    old('special_programs', [])
                                                                ),
                                                                true
                                                            )
                                                        )
                                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                    >

                                                    <span class="text-sm leading-5 text-slate-700">
                                                        {{ $program->name }}
                                                    </span>

                                                </label>

                                            @endforeach

                                        </div>

                                    @else

                                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                            <p class="text-sm text-slate-500">
                                                Belum ada Program Khusus untuk periode ini.
                                            </p>
                                        </div>

                                    @endif

                                    @error('special_programs')
                                        <p class="mt-3 text-xs text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                    @error('special_programs.*')
                                        <p class="mt-3 text-xs text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>

                            </div>

                            <div class="md:col-span-2">

                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Catatan
                                </label>

                                <textarea
                                    name="notes"
                                    rows="3"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >{{ old('notes') }}</textarea>

                            </div>

                        </div>

                    </section>

                    {{-- Submit --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">

                            <div>
                                <h3 class="font-bold text-slate-900">
                                    Pastikan data sudah benar
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Data akan disimpan setelah tombol kirim ditekan.
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200"
                            >
                                Kirim Pendaftaran
                            </button>

                        </div>

                    </section>

                </form>

            @endif

        </main>

        <footer class="border-t border-slate-200 bg-white">

            <div class="mx-auto max-w-7xl px-4 py-6 text-center text-xs text-slate-500 sm:px-6 lg:px-8">
                &copy; {{ now()->year }} {{ config('app.name') }}.
            </div>

        </footer>

    </div>

</body>
</html>