<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Status Pendaftaran | {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-800">

    @php
        $statusMeta = match ($registration->status) {
            'REGISTERED' => [
                'class' => 'border-blue-200 bg-blue-50 text-blue-700',
                'description' => 'Pendaftaran Anda telah tercatat dan sedang menunggu proses berikutnya.',
            ],
            'ACCEPTED' => [
                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'description' => 'Selamat, Anda telah dinyatakan diterima.',
            ],
            'REJECTED' => [
                'class' => 'border-red-200 bg-red-50 text-red-700',
                'description' => 'Pendaftaran Anda dinyatakan tidak diterima.',
            ],
            'REENROLLED' => [
                'class' => 'border-violet-200 bg-violet-50 text-violet-700',
                'description' => 'Proses daftar ulang Anda telah tercatat.',
            ],
            'WITHDRAWN' => [
                'class' => 'border-amber-200 bg-amber-50 text-amber-700',
                'description' => 'Pendaftaran tercatat telah mengundurkan diri.',
            ],
            default => [
                'class' => 'border-slate-200 bg-slate-50 text-slate-700',
                'description' => 'Status pendaftaran Anda tercatat di sistem.',
            ],
        };
    @endphp

    <div class="min-h-screen">

        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">
                        SPMB Online
                    </p>

                    <h1 class="mt-1 text-xl font-bold text-slate-900">
                        {{ config('app.name') }}
                    </h1>
                </div>

                <div class="hidden text-right sm:block">
                    <p class="text-xs text-slate-500">
                        Periode
                    </p>

                    <p class="font-semibold text-slate-900">
                        {{ $registration->period->name }}
                    </p>
                </div>

            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-8 text-center sm:px-8">

                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                        Status Pendaftaran
                    </p>

                    <h2 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">
                        {{ $registration->full_name }}
                    </h2>

                    <p class="mt-2 text-sm text-slate-600">
                        {{ $registration->registration_number }}
                    </p>

                </div>

                <div class="p-6 sm:p-8">

                    <div class="rounded-2xl border p-6 text-center {{ $statusMeta['class'] }}">

                        <p class="text-xs font-semibold uppercase tracking-[0.16em]">
                            Status Saat Ini
                        </p>

                        <p class="mt-2 text-2xl font-bold">
                            {{ $registration->statusLabel() }}
                        </p>

                        <p class="mt-3 text-sm leading-6">
                            {{ $statusMeta['description'] }}
                        </p>

                    </div>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">

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
                                -
                                {{ $registration->major->name }}
                            </p>
                        </div>

                    </div>

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

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">

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
                            href="{{ route('registration.create') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Form Pendaftaran
                        </a>

                    </div>

                </div>

            </section>

        </main>

    </div>

</body>
</html>
