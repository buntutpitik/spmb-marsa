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
        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">

                <a
                    href="{{ route('home') }}"
                    class="flex min-w-0 items-center gap-3"
                >
                    @if ($period?->school?->logo_path)
                        <img
                            src="{{ asset('storage/'.$period->school->logo_path) }}"
                            alt="{{ $period->school->name }}"
                            class="h-10 w-10 shrink-0 rounded-xl object-contain"
                        >
                    @endif

                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-blue-600">
                            SPMB Online
                        </p>

                        <p class="truncate text-sm font-bold text-slate-900 sm:text-base">
                            {{ $period?->school?->name ?? config('app.name') }}
                        </p>
                    </div>
                </a>

                <div class="flex shrink-0 items-center gap-3">
                    @if ($period)
                        <div class="hidden text-right md:block">
                            <p class="text-[11px] text-slate-500">
                                Periode Aktif
                            </p>

                            <p class="text-sm font-semibold text-slate-900">
                                {{ $period->name }}
                            </p>
                        </div>
                    @endif

                    <a
                        href="{{ route('home') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 sm:px-4 sm:text-sm"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path d="M19 12H5"></path>
                            <path d="m12 19-7-7 7-7"></path>
                        </svg>

                        <span class="hidden sm:inline">
                            Kembali ke Beranda
                        </span>

                        <span class="sm:hidden">
                            Beranda
                        </span>
                    </a>
                </div>

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

            @if ($period && $activePath)
                <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm font-semibold text-slate-900">
                                Lengkapi 8 bagian formulir
                            </p>

                            <p class="text-xs text-slate-500">
                                Tanda <span class="font-bold text-red-500">*</span> wajib diisi
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-px bg-slate-200 sm:grid-cols-4 lg:grid-cols-8">
                        @foreach ([
                            ['1', 'Pilihan'], ['2', 'Identitas'], ['3', 'Alamat Siswa'], ['4', 'Kontak'], ['5', 'Asal Sekolah'],
                            ['6', 'Jurusan'], ['7', 'Orang Tua'], ['8', 'Alamat Orang Tua'],
                        ] as [$number, $label])
                            <div class="flex items-center gap-3 bg-white px-4 py-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-xs font-bold text-blue-700">
                                    {{ $number }}
                                </span>

                                <span class="text-xs font-medium text-slate-600">
                                    {{ $label }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

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
                    <div
                        class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5"
                        role="alert"
                        aria-live="polite"
                    >
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
                                    Ada data yang belum benar.
                                </h3>

                                <p class="mt-1 text-sm leading-6 text-red-700">
                                    Periksa kembali bagian yang ditandai sebelum mengirim pendaftaran.
                                </p>

                                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-red-700">
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
                        originSchool: @js((string) old('origin_school_id', '')),
                        submitting: false,
                        sameParent: @js((string) old('same_parent_address', '0') === '1'),
                        copyAddress() {
                            if (!this.sameParent) return;
                            ['hamlet','rt','rw','village','district','city','province','postal_code'].forEach(field => {
                                const student = this.$el.querySelector('[name=' + field + ']');
                                const parent = this.$el.querySelector('[name=parent_' + field + ']');
                                if (student && parent) parent.value = student.value;
                            });
                        }
                    }"
                    @submit="submitting = true"
                    @input="if (sameParent && $event.target.name && ['hamlet','rt','rw','village','district','city','province','postal_code'].includes($event.target.name)) copyAddress()"
                    @change="if ($event.target.id === 'same_parent_address') { sameParent = $event.target.checked; copyAddress(); }"
                    x-init="if (sameParent) copyAddress()"
                >

                    @csrf

                    <input
                        type="hidden"
                        name="period_id"
                        value="{{ old('period_id', $period->id) }}"
                    >

                    {{-- Section 1 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5"><div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">1</div><div><h3 class="font-bold text-slate-900">Pilihan Pendaftaran</h3><p class="mt-1 text-sm text-slate-500">Periode dan jalur ditentukan otomatis oleh sistem.</p></div></div></div>
                        <div class="grid gap-5 p-6 md:grid-cols-2">
                        <div><label class="mb-2 block text-sm font-semibold text-slate-700">Periode SPMB</label><div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">{{ $period->name }}</div></div>
                        <div><label class="mb-2 block text-sm font-semibold text-slate-700">Jalur Aktif</label><div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-800">{{ $activePath->name }}<p class="mt-1 text-xs font-normal">Ditentukan otomatis berdasarkan tanggal pendaftaran.</p></div></div>
                        </div>
                    </section>

                    {{-- Section 2 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5"><div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">2</div><div><h3 class="font-bold text-slate-900">Identitas Siswa</h3><p class="mt-1 text-sm text-slate-500">Masukkan identitas sesuai dokumen resmi.</p></div></div></div>
                        <div class="grid gap-5 p-6 md:grid-cols-2">
                        <div class="md:col-span-2"><label for="full_name" class="mb-2 block text-sm font-semibold text-slate-700">Nama Lengkap <span class="text-red-500">*</span></label><input id="full_name" type="text" name="full_name" value="{{ old('full_name') }}" maxlength="150" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('full_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="nik" class="mb-2 block text-sm font-semibold text-slate-700">NIK <span class="text-red-500">*</span></label><input id="nik" type="text" name="nik" value="{{ old('nik') }}" inputmode="numeric" maxlength="16" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('nik')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="nisn" class="mb-2 block text-sm font-semibold text-slate-700">NISN</label><input id="nisn" type="text" name="nisn" value="{{ old('nisn') }}" inputmode="numeric" maxlength="20"  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('nisn')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="birth_place" class="mb-2 block text-sm font-semibold text-slate-700">Tempat Lahir</label><input id="birth_place" type="text" name="birth_place" value="{{ old('birth_place') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('birth_place')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="birth_date" class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Lahir</label><input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('birth_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="gender" class="mb-2 block text-sm font-semibold text-slate-700">Jenis Kelamin</label><select id="gender" name="gender" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"><option value="">Pilih jenis kelamin</option><option value="L" @selected(old('gender') === 'L')>Laki-laki</option><option value="P" @selected(old('gender') === 'P')>Perempuan</option></select></div>
                        <div class=""><label for="religion" class="mb-2 block text-sm font-semibold text-slate-700">Agama</label><input id="religion" type="text" name="religion" value="{{ old('religion') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('religion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        </div>
                    </section>

                    {{-- Section 3 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5"><div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">3</div><div><h3 class="font-bold text-slate-900">Alamat Siswa</h3><p class="mt-1 text-sm text-slate-500">Masukkan alamat tempat tinggal calon siswa.</p></div></div></div>
                        <div class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div class=""><label for="hamlet" class="mb-2 block text-sm font-semibold text-slate-700">Dusun</label><input id="hamlet" type="text" name="hamlet" value="{{ old('hamlet') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('hamlet')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="rt" class="mb-2 block text-sm font-semibold text-slate-700">RT</label><input id="rt" type="text" name="rt" value="{{ old('rt') }}" maxlength="10"  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('rt')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="rw" class="mb-2 block text-sm font-semibold text-slate-700">RW</label><input id="rw" type="text" name="rw" value="{{ old('rw') }}" maxlength="10"  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('rw')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="village" class="mb-2 block text-sm font-semibold text-slate-700">Desa / Kelurahan</label><input id="village" type="text" name="village" value="{{ old('village') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('village')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="district" class="mb-2 block text-sm font-semibold text-slate-700">Kecamatan</label><input id="district" type="text" name="district" value="{{ old('district') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('district')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="city" class="mb-2 block text-sm font-semibold text-slate-700">Kabupaten / Kota</label><input id="city" type="text" name="city" value="{{ old('city') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="province" class="mb-2 block text-sm font-semibold text-slate-700">Provinsi</label><input id="province" type="text" name="province" value="{{ old('province') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('province')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="postal_code" class="mb-2 block text-sm font-semibold text-slate-700">Kode Pos</label><input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code') }}" inputmode="numeric" maxlength="5"  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('postal_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        </div>
                    </section>

                    {{-- Section 4: Kontak Pendaftaran --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">4</div>
                                <div>
                                    <h3 class="font-bold text-slate-900">Kontak Pendaftaran</h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Nomor WhatsApp aktif untuk komunikasi pendaftaran.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <label for="whatsapp" class="mb-2 block text-sm font-semibold text-slate-700">
                                Nomor WhatsApp <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="whatsapp"
                                type="text"
                                name="whatsapp"
                                value="{{ old('whatsapp') }}"
                                maxlength="30"
                                placeholder="Contoh: 6281234567890"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >

                            @error('whatsapp')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror

                            <p class="mt-2 text-xs text-slate-500">
                                Gunakan nomor WhatsApp aktif yang dapat dihubungi.
                            </p>
                        </div>
                    </section>

                    {{-- Section 5 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5"><div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">5</div><div><h3 class="font-bold text-slate-900">Asal Sekolah</h3><p class="mt-1 text-sm text-slate-500">Pilih sekolah asal dan isi nilai kelulusan.</p></div></div></div>
                        <div class="grid gap-5 p-6 md:grid-cols-2">
                        <div class="md:col-span-2"><label for="origin_school_id" class="mb-2 block text-sm font-semibold text-slate-700">Asal Sekolah <span class="text-red-500">*</span></label><select id="origin_school_id" name="origin_school_id" x-model="originSchool" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"><option value="">Pilih asal sekolah</option>@foreach ($originSchools as $school)<option value="{{ $school->id }}" @selected((string) old('origin_school_id') === (string) $school->id)>{{ $school->name }}@if ($school->type) - {{ $school->type }}@endif</option>@endforeach<option value="OTHER" @selected(old('origin_school_id') === 'OTHER')>Lainnya</option></select>@error('origin_school_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class="md:col-span-2" x-show="originSchool === 'OTHER'" x-cloak><label for="origin_school_other" class="mb-2 block text-sm font-semibold text-slate-700">Nama Sekolah Asal <span class="text-red-500">*</span></label><input id="origin_school_other" type="text" name="origin_school_other" value="{{ old('origin_school_other') }}" maxlength="150" :required="originSchool === 'OTHER'" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('origin_school_other')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div class=""><label for="graduation_year" class="mb-2 block text-sm font-semibold text-slate-700">Tahun Lulus <span class="text-red-500">*</span></label><input id="graduation_year" type="number" name="graduation_year" value="{{ old('graduation_year') }}" min="2000" max="2100" step="1" required placeholder="Contoh: 2026" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('graduation_year')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        @if ($period->show_graduation_score)
                        <div class=""><label for="graduation_score" class="mb-2 block text-sm font-semibold text-slate-700">Nilai Kelulusan</label><input id="graduation_score" type="number" name="graduation_score" value="{{ old('graduation_score') }}" min="0" max="100" step="0.01"  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('graduation_score')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        @endif
                        </div>
                    </section>

                    {{-- Section 6 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5"><div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">6</div><div><h3 class="font-bold text-slate-900">Jurusan & Program Khusus</h3><p class="mt-1 text-sm text-slate-500">Pilih jurusan dan program khusus yang diminati.</p></div></div></div>
                        <div class="grid gap-5 p-6 md:grid-cols-2">
                        <div class="md:col-span-2"><label for="major_id" class="mb-2 block text-sm font-semibold text-slate-700">Jurusan <span class="text-red-500">*</span></label><select id="major_id" name="major_id" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"><option value="">Pilih jurusan</option>@foreach ($majors as $major)<option value="{{ $major->id }}" @selected(old('major_id') == $major->id)>{{ $major->code }} - {{ $major->name }}</option>@endforeach</select>@error('major_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>                            {{-- Program Khusus --}}
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


                        </div>
                    </section>

                    {{-- Section 7 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5"><div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">7</div><div><h3 class="font-bold text-slate-900">Data Orang Tua/Wali</h3><p class="mt-1 text-sm text-slate-500">Informasi orang tua calon siswa.</p></div></div></div>
                        <div class="grid gap-5 p-6 md:grid-cols-2">
                        <div class=""><label for="father_name" class="mb-2 block text-sm font-semibold text-slate-700">Nama Ayah</label><input id="father_name" type="text" name="father_name" value="{{ old('father_name') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('father_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="father_job" class="mb-2 block text-sm font-semibold text-slate-700">Pekerjaan Ayah</label><input id="father_job" type="text" name="father_job" value="{{ old('father_job') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('father_job')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="mother_name" class="mb-2 block text-sm font-semibold text-slate-700">Nama Ibu</label><input id="mother_name" type="text" name="mother_name" value="{{ old('mother_name') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('mother_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="mother_job" class="mb-2 block text-sm font-semibold text-slate-700">Pekerjaan Ibu</label><input id="mother_job" type="text" name="mother_job" value="{{ old('mother_job') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('mother_job')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        </div>
                    </section>

                    {{-- Section 8 --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-5"><div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-600">8</div><div><h3 class="font-bold text-slate-900">Alamat Orang Tua/Wali</h3><p class="mt-1 text-sm text-slate-500">Alamat orang tua/wali dapat berbeda dari alamat siswa.</p></div></div></div>
                        <div class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="md:col-span-2"><input type="hidden" name="same_parent_address" value="0"><label class="flex cursor-pointer items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm font-semibold text-blue-900"><input id="same_parent_address" type="checkbox" name="same_parent_address" value="1" @checked(old('same_parent_address', '0') == '1') class="h-4 w-4 rounded border-blue-300 text-blue-600">Alamat orang tua/wali sama dengan alamat siswa</label><p class="mt-2 text-xs text-slate-500">Jika dicentang, perubahan alamat siswa langsung disalin. Jika dilepas, alamat orang tua dapat diedit sendiri.</p></div><div class=""><label for="parent_hamlet" class="mb-2 block text-sm font-semibold text-slate-700">Dusun</label><input id="parent_hamlet" type="text" name="parent_hamlet" value="{{ old('parent_hamlet') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('parent_hamlet')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="parent_rt" class="mb-2 block text-sm font-semibold text-slate-700">RT</label><input id="parent_rt" type="text" name="parent_rt" value="{{ old('parent_rt') }}" maxlength="10"  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('parent_rt')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="parent_rw" class="mb-2 block text-sm font-semibold text-slate-700">RW</label><input id="parent_rw" type="text" name="parent_rw" value="{{ old('parent_rw') }}" maxlength="10"  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('parent_rw')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="parent_village" class="mb-2 block text-sm font-semibold text-slate-700">Desa / Kelurahan</label><input id="parent_village" type="text" name="parent_village" value="{{ old('parent_village') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('parent_village')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="parent_district" class="mb-2 block text-sm font-semibold text-slate-700">Kecamatan</label><input id="parent_district" type="text" name="parent_district" value="{{ old('parent_district') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('parent_district')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="parent_city" class="mb-2 block text-sm font-semibold text-slate-700">Kabupaten / Kota</label><input id="parent_city" type="text" name="parent_city" value="{{ old('parent_city') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('parent_city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="parent_province" class="mb-2 block text-sm font-semibold text-slate-700">Provinsi</label><input id="parent_province" type="text" name="parent_province" value="{{ old('parent_province') }}"   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('parent_province')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class=""><label for="parent_postal_code" class="mb-2 block text-sm font-semibold text-slate-700">Kode Pos</label><input id="parent_postal_code" type="text" name="parent_postal_code" value="{{ old('parent_postal_code') }}" inputmode="numeric" maxlength="5"  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">@error('parent_postal_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        </div>
                    </section>
                    {{-- Submit --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">

                            <div>
                                <h3 class="font-bold text-slate-900">
                                    Pastikan data sudah benar
                                </h3>

                                <p class="mt-2 text-xs leading-5 text-slate-400">
                                    Setelah berhasil, Anda akan diarahkan ke halaman bukti pendaftaran.
                                    Jangan menutup halaman saat data sedang dikirim.
                                </p>
                            </div>

                            <button
                                type="submit"
                                :disabled="submitting"
                                class="inline-flex min-w-[190px] items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <svg
                                    x-show="submitting"
                                    x-cloak
                                    class="h-4 w-4 animate-spin"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="9"
                                        stroke="currentColor"
                                        stroke-width="3"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"
                                    ></path>
                                </svg>

                                <span x-show="! submitting">
                                    Kirim Pendaftaran
                                </span>

                                <span x-show="submitting" x-cloak>
                                    Mengirim...
                                </span>
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
