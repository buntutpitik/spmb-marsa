<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pendaftaran Berhasil | {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-800">

    <div class="min-h-screen">

        {{-- Header --}}
        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">

                <a
                    href="{{ route('home') }}"
                    class="flex min-w-0 items-center gap-3"
                >
                    @if ($registration->period->school?->logo_path)
                        <img
                            src="{{ asset('storage/'.$registration->period->school->logo_path) }}"
                            alt="{{ $registration->period->school->name }}"
                            class="h-10 w-10 shrink-0 rounded-xl object-contain"
                        >
                    @endif

                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-blue-600">
                            SPMB Online
                        </p>

                        <p class="truncate text-sm font-bold text-slate-900 sm:text-base">
                            {{ $registration->period->school?->name ?? config('app.name') }}
                        </p>
                    </div>
                </a>

                <div class="flex shrink-0 items-center gap-3">
                    <div class="hidden text-right md:block">
                        <p class="text-[11px] text-slate-500">
                            Periode
                        </p>

                        <p class="text-sm font-semibold text-slate-900">
                            {{ $registration->period->name }}
                        </p>
                    </div>

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

        <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

            {{-- Success Hero --}}
            <section class="overflow-hidden rounded-3xl border border-emerald-200 bg-white shadow-sm">

                <div class="border-b border-emerald-100 bg-emerald-50/70 px-6 py-8 sm:px-8">

                    <div class="flex flex-col items-center text-center">

                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">

                            <svg
                                class="h-9 w-9 text-emerald-600"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="m8.5 12 2.3 2.3 4.7-5"></path>
                            </svg>

                        </div>

                        <h2 class="mt-5 text-2xl font-bold text-slate-900 sm:text-3xl">
                            Pendaftaran Berhasil
                        </h2>

                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                            Data pendaftaran Anda telah berhasil diterima oleh sistem SPMB MARSA.
                            Simpan nomor pendaftaran berikut untuk keperluan selanjutnya.
                        </p>

                    </div>

                </div>

                <div class="p-6 sm:p-8">

                    {{-- Registration Number --}}
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-6 text-center">

                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600">
                            Nomor Pendaftaran
                        </p>

                        <p class="mt-2 break-all text-2xl font-bold tracking-wide text-blue-900 sm:text-3xl">
                            {{ $registration->registration_number }}
                        </p>

                    </div>

                    {{-- Main Info --}}
                    <div class="mt-8 grid gap-4 sm:grid-cols-2">

                        <div class="rounded-2xl border border-slate-200 p-5">
                            <p class="text-xs font-medium text-slate-500">
                                Nama Calon Siswa
                            </p>

                            <p class="mt-1 font-semibold text-slate-900">
                                {{ $registration->full_name }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-5">
                            <p class="text-xs font-medium text-slate-500">
                                Status
                            </p>

                            <p class="mt-1 font-semibold text-emerald-700">
                                {{ $registration->statusLabel() }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-5">
                            <p class="text-xs font-medium text-slate-500">
                                Periode SPMB
                            </p>

                            <p class="mt-1 font-semibold text-slate-900">
                                {{ $registration->period->name }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-5">
                            <p class="text-xs font-medium text-slate-500">
                                Jalur Pendaftaran
                            </p>

                            <p class="mt-1 font-semibold text-slate-900">
                                {{ $registration->admissionPath->name }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-5 sm:col-span-2">
                            <p class="text-xs font-medium text-slate-500">
                                Jurusan
                            </p>

                            <p class="mt-1 font-semibold text-slate-900">
                                {{ $registration->major->code }}
                                —
                                {{ $registration->major->name }}
                            </p>
                        </div>

                    </div>


                    {{-- Special Programs --}}
                    @if ($registration->specialPrograms->isNotEmpty())

                        <div class="mt-8">

                            <h3 class="font-bold text-slate-900">
                                Program Khusus
                            </h3>

                            <div class="mt-3 flex flex-wrap gap-2">

                                @foreach ($registration->specialPrograms as $program)

                                    <span class="inline-flex rounded-full border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700">
                                        {{ $program->name }}
                                    </span>

                                @endforeach

                            </div>

                        </div>

                    @endif

                    {{-- Notice --}}
                    <div class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-5">

                        <div class="flex items-start gap-3">

                            <svg
                                class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 8v4"></path>
                                <path d="M12 16h.01"></path>
                            </svg>

                            <div>
                                <h3 class="text-sm font-semibold text-amber-900">
                                    Simpan akses pendaftaran Anda
                                </h3>

                                <p class="mt-1 text-sm leading-6 text-amber-800">
                                    Gunakan halaman ini untuk membuka status dan kartu pendaftaran Anda.
                                    Simpan nomor pendaftaran dan jangan membagikan tautan akses pendaftaran kepada orang lain.
                                </p>

                                <p class="mt-2 text-sm leading-6 text-amber-800">
                                    Tahap berikutnya adalah wawancara bersama orang tua/wali.
                                    Silakan datang ke Sekretariat SPMB MARSA pada jam kerja.
                                </p>
                            </div>

                        </div>

                    </div>

                    {{-- Actions --}}
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">

                        <a
                            href="{{ route(
                                'registration.status',
                                $registration->public_token
                            ) }}"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
                        >
                            Cek Status Pendaftaran
                        </a>

                        <a
                            href="{{ route(
                                'registration.card',
                                $registration->public_token
                            ) }}"
                            class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            Cetak Kartu Pendaftaran
                        </a>

                        <a
                            href="{{ route('home') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Kembali ke Beranda
                        </a>

                    </div>

                </div>

            </section>

        </main>

        <footer class="border-t border-slate-200 bg-white">

            <div class="mx-auto max-w-5xl px-4 py-6 text-center text-xs text-slate-500 sm:px-6 lg:px-8">
                &copy; {{ now()->year }} {{ config('app.name') }}.
            </div>

        </footer>

    </div>

</body>
</html>
