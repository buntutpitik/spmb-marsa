@extends('layouts.app')

@section('content')
    <div class="space-y-8">

        @php
            $messageTypeLabels = [
                'REGISTRATION_SUCCESS' => 'Pendaftaran Berhasil',
                'REGISTRATION_ACCEPTED' => 'Pendaftaran Diterima',
                'REGISTRATION_REJECTED' => 'Pendaftaran Ditolak',
                'REENROLLMENT_COMPLETE' => 'Daftar Ulang Selesai',
                'REGISTRATION_WITHDRAWN' => 'Mengundurkan Diri',

                'registration_success' => 'Pendaftaran Berhasil',
                'registration_accepted' => 'Pendaftaran Diterima',
                'registration_rejected' => 'Pendaftaran Ditolak',
                'reenrollment_complete' => 'Daftar Ulang Selesai',
                'registration_withdrawn' => 'Mengundurkan Diri',
            ];
        @endphp

        {{-- Heading --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>
                <p class="text-sm font-semibold text-emerald-600">
                    Komunikasi
                </p>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Riwayat WhatsApp
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Pantau status pengiriman notifikasi WhatsApp calon siswa pada setiap periode SPMB.
                </p>
            </div>

            @if ($selectedPeriod)
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">

                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        Periode Dipilih
                    </div>

                    <div class="mt-1 flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <span
                            class="h-2 w-2 rounded-full {{ $selectedPeriod->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"
                        ></span>

                        {{ $selectedPeriod->name }}
                    </div>

                </div>
            @endif

        </div>

        {{-- Summary --}}
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Total --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Total
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            {{ number_format($summary['total']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                        <i
                            data-lucide="messages-square"
                            class="h-5 w-5"
                        ></i>
                    </div>
                </div>
            </div>

            {{-- Pending --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Menunggu
                        </p>

                        <p class="mt-2 text-2xl font-bold text-amber-600">
                            {{ number_format($summary['pending']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <i
                            data-lucide="clock-3"
                            class="h-5 w-5"
                        ></i>
                    </div>
                </div>
            </div>

            {{-- Success --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Berhasil
                        </p>

                        <p class="mt-2 text-2xl font-bold text-emerald-600">
                            {{ number_format($summary['success']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <i
                            data-lucide="circle-check-big"
                            class="h-5 w-5"
                        ></i>
                    </div>
                </div>
            </div>

            {{-- Failed --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Gagal
                        </p>

                        <p class="mt-2 text-2xl font-bold text-rose-600">
                            {{ number_format($summary['failed']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                        <i
                            data-lucide="circle-alert"
                            class="h-5 w-5"
                        ></i>
                    </div>
                </div>
            </div>

        </section>

        {{-- Filter --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">

            <form
                method="GET"
                action="{{ route('admin.whatsapp-logs.index') }}"
                class="space-y-4"
            >

                <div class="flex flex-col gap-3 lg:flex-row lg:items-end">

                    <div class="min-w-0 flex-1">
                        <label
                            for="q"
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                        >
                            Pencarian
                        </label>

                        <div class="relative">
                            <i
                                data-lucide="search"
                                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            ></i>

                            <input
                                id="q"
                                type="text"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="Nama, nomor pendaftaran, WhatsApp, message ID..."
                                class="w-full rounded-xl border border-slate-300 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                        </div>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            <i
                                data-lucide="search"
                                class="h-4 w-4"
                            ></i>

                            Terapkan
                        </button>

                        <a
                            href="{{ route('admin.whatsapp-logs.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>

                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">

                    {{-- Period --}}
                    <div>
                        <label
                            for="period_id"
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                        >
                            Periode
                        </label>

                        <select
                            id="period_id"
                            name="period_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                        >
                            @foreach ($periods as $period)
                                <option
                                    value="{{ $period->id }}"
                                    @selected(
                                        $selectedPeriod
                                        && $selectedPeriod->id === $period->id
                                    )
                                >
                                    {{ $period->name }}
                                    {{ $period->is_active ? '— Aktif' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label
                            for="status"
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                        >
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                        >
                            <option value="">
                                Semua Status
                            </option>

                            @foreach ($statuses as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(request('status') === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Message Type --}}
                    <div>
                        <label
                            for="message_type"
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                        >
                            Jenis Pesan
                        </label>

                        <select
                            id="message_type"
                            name="message_type"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                        >
                            <option value="">
                                Semua Jenis
                            </option>

                            @foreach ($messageTypes as $messageType)
                                <option
                                    value="{{ $messageType }}"
                                    @selected(request('message_type') === $messageType)
                                >
                                    {{ $messageTypeLabels[$messageType] ?? str($messageType)->replace('_', ' ')->title() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

            </form>

        </section>

        {{-- Table --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">
                    Log Pengiriman
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Menampilkan riwayat notifikasi WhatsApp terbaru.
                </p>
            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Waktu
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Pendaftar
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                WhatsApp
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Jenis
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Status
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Percobaan
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Keterangan
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">

                        @forelse ($logs as $log)

                            <tr class="transition hover:bg-slate-50/70">

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                    <div class="font-medium text-slate-800">
                                        {{ $log->created_at?->format('d/m/Y') ?? '-' }}
                                    </div>

                                    <div class="mt-0.5 text-xs text-slate-400">
                                        {{ $log->created_at?->format('H:i:s') ?? '-' }}
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    @if ($log->registration)

                                        <a
                                            href="{{ route('admin.registrations.show', $log->registration) }}"
                                            class="font-semibold text-slate-800 transition hover:text-emerald-600"
                                        >
                                            {{ $log->registration->full_name }}
                                        </a>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $log->registration->registration_number }}
                                        </div>

                                    @else
                                        <span class="text-sm text-slate-400">
                                            Tidak tersedia
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                    {{ $log->phone }}
                                </td>

                                <td class="px-5 py-4">
                                    <div class="text-sm font-medium text-slate-700">
                                        {{ $messageTypeLabels[$log->message_type] ?? str($log->message_type ?? '-')->replace('_', ' ')->title() }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-400">
                                        {{ $log->provider }}
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    @php
                                        $statusClasses = match ($log->status) {
                                            'SUCCESS' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                            'FAILED' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                            default => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        };

                                        $statusLabel = match ($log->status) {
                                            'SUCCESS' => 'Berhasil',
                                            'FAILED' => 'Gagal',
                                            default => 'Menunggu',
                                        };
                                    @endphp

                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}"
                                    >
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                    {{ $log->attempt_count }}
                                </td>

                                <td class="min-w-[240px] px-5 py-4">

                                    @if ($log->status === 'FAILED')
                                        <p class="text-sm leading-5 text-rose-600">
                                            {{ $log->error_message ?: 'Pengiriman gagal.' }}
                                        </p>

                                        @if ($log->failed_at)
                                            <p class="mt-1 text-xs text-slate-400">
                                                {{ $log->failed_at->format('d/m/Y H:i:s') }}
                                            </p>
                                        @endif

                                    @elseif ($log->status === 'SUCCESS')
                                        <p class="text-sm text-slate-600">
                                            Terkirim
                                        </p>

                                        @if ($log->sent_at)
                                            <p class="mt-1 text-xs text-slate-400">
                                                {{ $log->sent_at->format('d/m/Y H:i:s') }}
                                            </p>
                                        @endif

                                    @else
                                        <p class="text-sm text-amber-600">
                                            Menunggu proses queue
                                        </p>
                                    @endif

                                    @if ($log->provider_message_id)
                                        <p
                                            class="mt-1 max-w-xs truncate text-[11px] text-slate-400"
                                            title="{{ $log->provider_message_id }}"
                                        >
                                            ID: {{ $log->provider_message_id }}
                                        </p>
                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="7"
                                    class="px-5 py-16 text-center"
                                >
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                        <i
                                            data-lucide="message-circle-off"
                                            class="h-5 w-5 text-slate-400"
                                        ></i>
                                    </div>

                                    <h3 class="mt-4 text-sm font-semibold text-slate-800">
                                        Belum ada riwayat WhatsApp
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Log pengiriman yang sesuai filter akan tampil di sini.
                                    </p>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            @if ($logs->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $logs->links() }}
                </div>
            @endif

        </section>

    </div>
@endsection