<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>SPMB {{ $school->name }}</title>

    @if ($school->favicon_path)
        <link
            rel="icon"
            href="{{ asset('storage/'.$school->favicon_path) }}"
        >
    @endif

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body class="bg-slate-50 text-slate-800 antialiased">

<div class="min-h-screen">

    {{-- =========================================================
         HEADER
    ========================================================== --}}
    <header
        class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur"
    >
        <div
            class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8"
        >
            <a
                href="{{ route('home') }}"
                class="flex min-w-0 items-center gap-3"
            >
                @if ($school->logo_path)
                    <img
                        src="{{ asset('storage/'.$school->logo_path) }}"
                        alt="Logo {{ $school->name }}"
                        class="h-11 w-11 shrink-0 object-contain"
                    >
                @else
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold text-white"
                    >
                        SPMB
                    </div>
                @endif

                <div class="min-w-0">
                    <p
                        class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600"
                    >
                        SPMB Online
                    </p>

                    <p
                        class="truncate text-sm font-bold text-slate-900 sm:text-base"
                    >
                        {{ $school->name }}
                    </p>
                </div>
            </a>

            <nav
                class="hidden items-center gap-7 text-sm font-semibold text-slate-600 lg:flex"
            >
                <a
                    href="#beranda"
                    class="transition hover:text-blue-600"
                >
                    Beranda
                </a>

                <a
                    href="#program-keahlian"
                    class="transition hover:text-blue-600"
                >
                    Program Keahlian
                </a>

                <a
                    href="#informasi"
                    class="transition hover:text-blue-600"
                >
                    Informasi
                </a>

                <a
                    href="#cara-mendaftar"
                    class="transition hover:text-blue-600"
                >
                    Cara Mendaftar
                </a>

                @if ($setting?->show_contact)
                    <a
                        href="#kontak"
                        class="transition hover:text-blue-600"
                    >
                        Kontak
                    </a>
                @endif
            </nav>

            <div class="flex shrink-0 items-center gap-2">
                <a
                    href="{{ route('login') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-700"
                >
                    Login Admin
                </a>

                @if ($registrationState === 'OPEN')
                <a
                    href="{{ route('registration.create') }}"
                    class="hidden shrink-0 items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 sm:inline-flex"
                >
                    Daftar Sekarang
                </a>
            @elseif ($registrationState === 'UPCOMING')
                <span
                    class="hidden shrink-0 rounded-xl bg-amber-50 px-5 py-2.5 text-sm font-semibold text-amber-700 sm:inline-flex"
                >
                    Belum Dibuka
                </span>
            @elseif ($registrationState === 'CLOSED')
                <span
                    class="hidden shrink-0 rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-500 sm:inline-flex"
                >
                    Pendaftaran Ditutup
                </span>
            @else
                <span
                    class="hidden shrink-0 rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-500 sm:inline-flex"
                >
                    Belum Tersedia
                </span>
            @endif
            </div>

        </div>
    </header>

    <main>

        {{-- =====================================================
             HERO
        ====================================================== --}}
        <section
            id="beranda"
            class="relative overflow-hidden bg-white"
        >
            <div
                class="absolute inset-x-0 top-0 -z-0 h-80 bg-gradient-to-b from-blue-50 via-sky-50/60 to-transparent"
            ></div>

            <div
                class="relative mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 sm:py-16 lg:grid-cols-[1.15fr_.85fr] lg:px-8 lg:py-18"
            >
                <div class="flex flex-col justify-center">

                    @if ($period)
                        <div
                            class="inline-flex w-fit items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3.5 py-2 text-xs font-bold text-blue-700"
                        >
                            <span
                                class="h-2 w-2 rounded-full {{ $registrationAvailable ? 'bg-emerald-500' : 'bg-amber-500' }}"
                            ></span>

                            SPMB {{ $period->name }}
                        </div>
                    @else
                        <div
                            class="inline-flex w-fit rounded-full border border-slate-200 bg-slate-100 px-3.5 py-2 text-xs font-bold text-slate-600"
                        >
                            Informasi SPMB
                        </div>
                    @endif

                    <h1
                        class="mt-6 max-w-3xl text-4xl font-black tracking-tight text-slate-950 sm:text-5xl lg:text-[3.4rem] lg:leading-[1.06]"
                    >
                        {{ $setting?->hero_title
                            ?: 'Penerimaan Murid Baru '.$school->name }}
                    </h1>

                    @if ($setting?->hero_subtitle)
                        <p
                            class="mt-5 text-xl font-semibold text-blue-700"
                        >
                            {{ $setting->hero_subtitle }}
                        </p>
                    @endif

                    <p
                        class="mt-5 max-w-2xl text-base leading-8 text-slate-600 sm:text-lg"
                    >
                        {{ $setting?->hero_description
                            ?: 'Informasi dan pendaftaran SPMB dapat diakses secara online melalui halaman resmi ini.' }}
                    </p>

                    <div
                        class="mt-8 flex flex-col gap-3 sm:flex-row"
                    >
                        @if ($registrationState === 'OPEN')
                            <a
                                href="{{ route('registration.create') }}"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700"
                            >
                                Daftar Sekarang

                                <svg
                                    class="ml-2 h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M5 12h14"></path>
                                    <path d="m13 6 6 6-6 6"></path>
                                </svg>
                            </a>
                        @elseif ($registrationState === 'UPCOMING')
                            <span
                                class="inline-flex items-center justify-center rounded-xl bg-amber-100 px-6 py-3.5 text-sm font-bold text-amber-800"
                            >
                                Pendaftaran Dibuka
                                {{ $period?->registration_open
                                    ? $period->registration_open->translatedFormat('d F Y')
                                    : 'Segera' }}
                            </span>
                        @elseif ($registrationState === 'CLOSED')
                            <span
                                class="inline-flex items-center justify-center rounded-xl bg-slate-200 px-6 py-3.5 text-sm font-bold text-slate-600"
                            >
                                Pendaftaran Sudah Ditutup
                            </span>
                        @else
                            <span
                                class="inline-flex items-center justify-center rounded-xl bg-slate-200 px-6 py-3.5 text-sm font-bold text-slate-600"
                            >
                                Pendaftaran Belum Tersedia
                            </span>
                        @endif

                        <a
                            href="#informasi"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-bold text-slate-700 transition hover:border-blue-300 hover:text-blue-700"
                        >
                            Lihat Informasi
                        </a>
                    </div>

                    @if ($activePath)
                        <p
                            class="mt-5 text-sm text-slate-500"
                        >
                            Jalur yang sedang dibuka:
                            <span class="font-bold text-slate-800">
                                {{ $activePath->name }}
                            </span>
                        </p>
                    @endif
                </div>

                {{-- Period information --}}
                <div
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600"
                            >
                                Informasi Periode
                            </p>

                            <h2
                                class="mt-2 text-2xl font-bold text-slate-950"
                            >
                                Jadwal SPMB
                            </h2>
                        </div>

                                                @php
                            $stateClasses = match ($registrationState) {
                                'OPEN' => 'bg-emerald-50 text-emerald-700',
                                'UPCOMING' => 'bg-amber-50 text-amber-700',
                                'CLOSED' => 'bg-slate-100 text-slate-600',
                                default => 'bg-slate-100 text-slate-600',
                            };

                            $stateLabel = match ($registrationState) {
                                'OPEN' => 'Sedang Dibuka',
                                'UPCOMING' => 'Belum Dibuka',
                                'CLOSED' => 'Sudah Ditutup',
                                default => 'Belum Tersedia',
                            };
                        @endphp

                        <div
                            class="rounded-2xl {{ $stateClasses }} px-3 py-2 text-xs font-bold"
                        >
                            {{ $stateLabel }}
                        </div>
                    </div>

                    @if ($period)
                        <div class="mt-7 space-y-4">
                            <div
                                class="rounded-2xl bg-slate-50 p-5"
                            >
                                <p class="text-xs font-medium text-slate-500">
                                    Tahun Pelajaran
                                </p>

                                <p class="mt-1 text-lg font-bold text-slate-900">
                                    {{ $period->name }}
                                </p>
                            </div>

                            <div
                                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2"
                            >
                                <div
                                    class="rounded-2xl border border-slate-200 p-5"
                                >
                                    <p class="text-xs font-medium text-slate-500">
                                        Pendaftaran Dibuka
                                    </p>

                                    <p class="mt-2 font-bold text-slate-900">
                                        {{ $period->registration_open
                                            ? $period->registration_open
                                                ->translatedFormat('d F Y')
                                            : 'Belum ditentukan' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-slate-200 p-5"
                                >
                                    <p class="text-xs font-medium text-slate-500">
                                        Pendaftaran Ditutup
                                    </p>

                                    <p class="mt-2 font-bold text-slate-900">
                                        {{ $period->registration_close
                                            ? $period->registration_close
                                                ->translatedFormat('d F Y')
                                            : 'Belum ditentukan' }}
                                    </p>
                                </div>
                            </div>

                            @if ($activePath)
                                <div
                                    class="rounded-2xl border border-blue-200 bg-blue-50 p-5"
                                >
                                    <p class="text-xs font-medium text-blue-600">
                                        Jalur Aktif Saat Ini
                                    </p>

                                    <p class="mt-1 font-bold text-blue-950">
                                        {{ $activePath->name }}
                                    </p>

                                    @if ($activePath->description)
                                        <p
                                            class="mt-2 text-sm leading-6 text-blue-800/80"
                                        >
                                            {{ $activePath->description }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                        <div
                            class="mt-7 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm leading-6 text-slate-600"
                        >
                            Periode SPMB yang aktif belum tersedia.
                            Silakan pantau halaman ini untuk informasi
                            pembukaan pendaftaran berikutnya.
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- =====================================================
             ANNOUNCEMENT
        ====================================================== --}}
        @if (
            $setting?->show_announcement
            && (
                $setting->announcement_title
                || $setting->announcement_body
            )
        )
            <section class="px-4 py-6 sm:px-6 lg:px-8">
                <div
                    class="mx-auto max-w-7xl rounded-3xl border border-amber-200 bg-amber-50 p-6 sm:p-8"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-start"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M3 11v2"></path>
                                <path d="m5 10 11-5v14L5 14z"></path>
                                <path d="M8 14v5h4"></path>
                            </svg>
                        </div>

                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700"
                            >
                                Pengumuman Terbaru
                            </p>

                            @if ($setting->announcement_title)
                                <h2
                                    class="mt-2 text-xl font-bold text-amber-950 sm:text-2xl"
                                >
                                    {{ $setting->announcement_title }}
                                </h2>
                            @endif

                            @if ($setting->announcement_body)
                                <p
                                    class="mt-3 whitespace-pre-line text-sm leading-7 text-amber-900/80 sm:text-base"
                                >{{ $setting->announcement_body }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- =====================================================
             PROGRAM KEAHLIAN
        ====================================================== --}}
        @if ($period && $period->majors->isNotEmpty())
            <section
                id="program-keahlian"
                class="px-4 py-16 sm:px-6 lg:px-8 lg:py-20"
            >
                <div class="mx-auto max-w-7xl">

                    <div class="max-w-2xl">
                        <p
                            class="text-sm font-bold uppercase tracking-[0.16em] text-blue-600"
                        >
                            Pilihan Pendidikan
                        </p>

                        <h2
                            class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl"
                        >
                            Program Keahlian
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            Pilih program keahlian yang sesuai dengan
                            minat dan rencana masa depan Anda.
                        </p>
                    </div>

                    <div
                        class="mt-9 grid gap-5 md:grid-cols-2 lg:grid-cols-3"
                    >
                        @foreach ($period->majors as $major)
                            <article
                                class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg"
                            >
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-sm font-black text-blue-700"
                                >
                                    {{ $major->short_name ?: $major->code }}
                                </div>

                                <h3
                                    class="mt-5 text-lg font-bold text-slate-950"
                                >
                                    {{ $major->name }}
                                </h3>

                                @if ($major->description)
                                    <p
                                        class="mt-3 text-sm leading-6 text-slate-600"
                                    >
                                        {{ $major->description }}
                                    </p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- =====================================================
             INFORMATION GRID
        ====================================================== --}}
        <section
            id="informasi"
            class="border-y border-slate-200 bg-white px-4 py-16 sm:px-6 lg:px-8 lg:py-20"
        >
            <div class="mx-auto max-w-7xl">
                <div class="max-w-2xl">
                    <p
                        class="text-sm font-bold uppercase tracking-[0.16em] text-blue-600"
                    >
                        Informasi Penting
                    </p>

                    <h2
                        class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl"
                    >
                        Informasi Pendaftaran
                    </h2>
                </div>

                <div
                    class="mt-9 grid gap-6 lg:grid-cols-2"
                >
                    @if ($period && $period->admissionPaths->isNotEmpty())
                        <article
                            class="rounded-3xl border border-slate-200 p-6 sm:p-8"
                        >
                            <h3 class="text-xl font-bold text-slate-950">
                                Jalur Pendaftaran
                            </h3>

                            <div class="mt-5 space-y-4">
                                @foreach ($period->admissionPaths as $path)
                                    <div
                                        class="rounded-2xl {{ $activePath?->id === $path->id ? 'border border-blue-200 bg-blue-50' : 'bg-slate-50' }} p-5"
                                    >
                                        <div
                                            class="flex flex-wrap items-center justify-between gap-2"
                                        >
                                            <p class="font-bold text-slate-900">
                                                {{ $path->name }}
                                            </p>

                                            @if ($activePath?->id === $path->id)
                                                <span
                                                    class="rounded-full bg-blue-600 px-2.5 py-1 text-[11px] font-bold text-white"
                                                >
                                                    Sedang Dibuka
                                                </span>
                                            @endif
                                        </div>

                                        @if ($path->description)
                                            <p
                                                class="mt-2 text-sm leading-6 text-slate-600"
                                            >
                                                {{ $path->description }}
                                            </p>
                                        @endif

                                        @if (
                                            $path->start_date
                                            || $path->end_date
                                        )
                                            <p
                                                class="mt-3 text-xs font-medium text-slate-500"
                                            >
                                                {{ $path->start_date
                                                    ? $path->start_date->translatedFormat('d F Y')
                                                    : 'Tanpa batas awal' }}
                                                &ndash;
                                                {{ $path->end_date
                                                    ? $path->end_date->translatedFormat('d F Y')
                                                    : 'Tanpa batas akhir' }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endif

                    @if (
                        $setting?->show_requirements
                        && count($requirements)
                    )
                        <article
                            class="rounded-3xl border border-slate-200 p-6 sm:p-8"
                        >
                            <h3 class="text-xl font-bold text-slate-950">
                                Persyaratan Pendaftaran
                            </h3>

                            <ul class="mt-5 space-y-4">
                                @foreach ($requirements as $requirement)
                                    <li class="flex items-start gap-3">
                                        <span
                                            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2.5"
                                            >
                                                <path d="m5 12 4 4L19 6"></path>
                                            </svg>
                                        </span>

                                        <span
                                            class="text-sm leading-6 text-slate-700"
                                        >
                                            {{ $requirement }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    @endif
                </div>

                @if (
                    $period
                    && $period->specialPrograms->isNotEmpty()
                )
                    <div class="mt-6">
                        <article
                            class="rounded-3xl border border-violet-200 bg-violet-50/50 p-6 sm:p-8"
                        >
                            <h3 class="text-xl font-bold text-slate-950">
                                Program Khusus
                            </h3>

                            <div
                                class="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3"
                            >
                                @foreach ($period->specialPrograms as $program)
                                    <div
                                        class="rounded-2xl bg-white p-5 shadow-sm"
                                    >
                                        <p class="font-bold text-violet-900">
                                            {{ $program->name }}
                                        </p>

                                        @if ($program->description)
                                            <p
                                                class="mt-2 text-sm leading-6 text-slate-600"
                                            >
                                                {{ $program->description }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    </div>
                @endif
            </div>
        </section>

        {{-- =====================================================
             REGISTRATION STEPS
        ====================================================== --}}
        @if (
            $setting?->show_registration_steps
            && count($registrationSteps)
        )
            <section
                id="cara-mendaftar"
                class="px-4 py-16 sm:px-6 lg:px-8 lg:py-20"
            >
                <div class="mx-auto max-w-7xl">

                    <div class="mx-auto max-w-2xl text-center">
                        <p
                            class="text-sm font-bold uppercase tracking-[0.16em] text-blue-600"
                        >
                            Mudah &amp; Praktis
                        </p>

                        <h2
                            class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl"
                        >
                            Cara Mendaftar
                        </h2>

                        <p class="mt-4 leading-7 text-slate-600">
                            Ikuti langkah pendaftaran berikut secara
                            berurutan.
                        </p>
                    </div>

                    <div
                        class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-4"
                    >
                        @foreach ($registrationSteps as $step)
                            <div
                                class="relative rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                            >
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-sm font-black text-white"
                                >
                                    {{ $loop->iteration }}
                                </div>

                                <p
                                    class="mt-5 text-sm font-semibold leading-6 text-slate-800"
                                >
                                    {{ $step }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    @if ($registrationAvailable)
                        <div class="mt-9 text-center">
                            <a
                                href="{{ route('registration.create') }}"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700"
                            >
                                Mulai Pendaftaran
                            </a>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- =====================================================
             REENROLLMENT
        ====================================================== --}}
        @if (
            $setting?->show_reenrollment_information
            && $setting->reenrollment_information
        )
            <section
                class="bg-slate-900 px-4 py-16 text-white sm:px-6 lg:px-8"
            >
                <div
                    class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[.7fr_1.3fr]"
                >
                    <div>
                        <p
                            class="text-sm font-bold uppercase tracking-[0.16em] text-sky-300"
                        >
                            Tahap Selanjutnya
                        </p>

                        <h2
                            class="mt-3 text-3xl font-black tracking-tight"
                        >
                            Informasi Daftar Ulang
                        </h2>
                    </div>

                    <div
                        class="rounded-3xl border border-white/10 bg-white/5 p-6 sm:p-8"
                    >
                        <p
                            class="whitespace-pre-line text-sm leading-7 text-slate-200 sm:text-base"
                        >{{ $setting->reenrollment_information }}</p>

                        @if (
                            $period
                            && $period->default_reenroll_fee > 0
                        )
                            <div
                                class="mt-6 border-t border-white/10 pt-5"
                            >
                                <p class="text-xs text-slate-400">
                                    Biaya Daftar Ulang
                                </p>

                                <p
                                    class="mt-1 text-2xl font-black text-white"
                                >
                                    Rp {{ number_format(
                                        $period->default_reenroll_fee,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- =====================================================
             CONTACT
        ====================================================== --}}
        @if ($setting?->show_contact)
            <section
                id="kontak"
                class="bg-white px-4 py-16 sm:px-6 lg:px-8"
            >
                <div
                    class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[.8fr_1.2fr]"
                >
                    <div>
                        <p
                            class="text-sm font-bold uppercase tracking-[0.16em] text-blue-600"
                        >
                            Butuh Bantuan?
                        </p>

                        <h2
                            class="mt-3 text-3xl font-black tracking-tight text-slate-950"
                        >
                            Kontak Panitia
                        </h2>

                        <p
                            class="mt-4 max-w-lg leading-7 text-slate-600"
                        >
                            Hubungi panitia SPMB jika membutuhkan
                            informasi lebih lanjut mengenai proses
                            pendaftaran.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @if ($school->whatsapp)
                            <div
                                class="rounded-2xl border border-slate-200 p-5"
                            >
                                <p class="text-xs font-medium text-slate-500">
                                    WhatsApp
                                </p>

                                <p
                                    class="mt-2 font-bold text-slate-900"
                                >
                                    {{ $school->whatsapp }}
                                </p>
                            </div>
                        @endif

                        @if ($school->phone)
                            <div
                                class="rounded-2xl border border-slate-200 p-5"
                            >
                                <p class="text-xs font-medium text-slate-500">
                                    Telepon
                                </p>

                                <p
                                    class="mt-2 font-bold text-slate-900"
                                >
                                    {{ $school->phone }}
                                </p>
                            </div>
                        @endif

                        @if ($school->email)
                            <div
                                class="rounded-2xl border border-slate-200 p-5"
                            >
                                <p class="text-xs font-medium text-slate-500">
                                    Email
                                </p>

                                <p
                                    class="mt-2 break-all font-bold text-slate-900"
                                >
                                    {{ $school->email }}
                                </p>
                            </div>
                        @endif

                        @if ($school->address)
                            <div
                                class="rounded-2xl border border-slate-200 p-5"
                            >
                                <p class="text-xs font-medium text-slate-500">
                                    Alamat
                                </p>

                                <p
                                    class="mt-2 text-sm font-semibold leading-6 text-slate-900"
                                >
                                    {{ $school->address }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

    </main>

    {{-- =========================================================
         FOOTER
    ========================================================== --}}
    <footer class="border-t border-slate-200 bg-slate-50">
        <div
            class="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-8 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8"
        >
            <div>
                <p class="font-bold text-slate-900">
                    {{ $school->name }}
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    Sistem Penerimaan Murid Baru Online
                </p>
            </div>

            <div
                class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-medium text-slate-500"
            >
                @if ($school->website)
                    <a
                        href="{{ $school->website }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="transition hover:text-blue-600"
                    >
                        Website Sekolah
                    </a>
                @endif

                <span>
                    &copy; {{ now()->year }}
                    {{ $school->name }}
                </span>
            </div>
        </div>
    </footer>

</div>

</body>
</html>