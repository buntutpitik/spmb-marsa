@extends('layouts.app')

@section('content')
    @php
        $statusLabel = match ($registration->status) {
            'REGISTERED' => 'Terdaftar',
            'ACCEPTED' => 'Diterima',
            'REJECTED' => 'Ditolak',
            'REENROLLED' => 'Daftar Ulang',
            'WITHDRAWN' => 'Mengundurkan Diri',
            default => $registration->status,
        };

        $statusClass = match ($registration->status) {
            'REGISTERED' => 'bg-slate-100 text-slate-700',
            'ACCEPTED' => 'bg-emerald-50 text-emerald-700',
            'REJECTED' => 'bg-red-50 text-red-700',
            'REENROLLED' => 'bg-violet-50 text-violet-700',
            'WITHDRAWN' => 'bg-amber-50 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };

        $isReadOnlyPeriod =
            $selectedPeriod->status !== 'OPEN'
            || ! $selectedPeriod->is_active;
    @endphp

    <div class="space-y-8">

        {{-- Heading --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>

                <a
                    href="{{ route('admin.registrations.index', [
                        'period_id' => $registration->period_id,
                    ]) }}"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 hover:text-emerald-700"
                >
                    <i
                        data-lucide="arrow-left"
                        class="h-4 w-4"
                    ></i>

                    Data Pendaftaran
                </a>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    {{ $registration->full_name }}
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    {{ $registration->registration_number }}
                </p>

            </div>

            <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                <span class="inline-flex rounded-full px-3 py-1.5 text-sm font-semibold {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>

                <a
                    href="{{ route(
                        'admin.registrations.card',
                        $registration
                    ) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <i
                        data-lucide="printer"
                        class="h-4 w-4"
                    ></i>

                    Print Kartu
                </a>

                @if (! $isReadOnlyPeriod)
                    <a
                        href="{{ route('admin.registrations.edit', [
                            'registration' => $registration,
                            'period_id' => $selectedPeriod->id,
                        ]) }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                    >
                        <i
                            data-lucide="pencil"
                            class="h-4 w-4"
                        ></i>

                        Edit Data
                    </a>
                @endif
            </div>

        </div>

        {{-- Flash Message --}}
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('status'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">
                {{ $errors->first('status') }}
            </div>
        @endif

        @if ($errors->has('payment'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">
                {{ $errors->first('payment') }}
            </div>
        @endif

        @if ($isReadOnlyPeriod)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <i
                            data-lucide="archive"
                            class="h-5 w-5"
                        ></i>
                    </div>

                    <div>
                        <div class="font-bold text-amber-900">
                            Data Historis — Mode Baca Saja
                        </div>

                        <p class="mt-1 text-sm leading-6 text-amber-800">
                            Periode {{ $selectedPeriod->name }} telah ditutup.
                            Data ditampilkan sebagai arsip dan tidak dapat diubah.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Aksi Pendaftaran --}}
        @if (
            ! $isReadOnlyPeriod
            && in_array(
                $registration->status,
                ['REGISTERED', 'ACCEPTED', 'REENROLLED'],
                true
            )
        )

            <section
                x-data="{
                    actionOpen: false,
                    targetStatus: '',
                    actionTitle: '',
                    actionDescription: '',
                    actionButton: '',
                    actionClass: '',

                    openAction(status) {
                        this.targetStatus = status;

                        if (status === 'ACCEPTED') {
                            this.actionTitle = 'Terima Pendaftar';
                            this.actionDescription = 'Pendaftar akan diubah menjadi status Diterima.';
                            this.actionButton = 'Ya, Terima';
                            this.actionClass = 'bg-emerald-600 hover:bg-emerald-700';
                        }

                        if (status === 'REJECTED') {
                            this.actionTitle = 'Tolak Pendaftar';
                            this.actionDescription = 'Pendaftar akan diubah menjadi status Ditolak.';
                            this.actionButton = 'Ya, Tolak';
                            this.actionClass = 'bg-red-600 hover:bg-red-700';
                        }

                        if (status === 'WITHDRAWN') {
                            this.actionTitle = 'Mengundurkan Diri';
                            this.actionDescription = 'Status pendaftar akan diubah menjadi Mengundurkan Diri.';
                            this.actionButton = 'Ya, Proses';
                            this.actionClass = 'bg-amber-600 hover:bg-amber-700';
                        }

                        this.actionOpen = true;
                    }
                }"
                class="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >

                <div class="border-b border-slate-100 px-6 py-5">

                    <h2 class="font-bold text-slate-900">
                        Aksi Pendaftaran
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Kelola status pendaftar sesuai proses penerimaan.
                    </p>

                </div>

                <div class="p-6">

                    @if ($registration->status === 'REGISTERED')

                        <div class="flex flex-wrap gap-3">

                            <button
                                type="button"
                                @click="openAction('ACCEPTED')"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                            >
                                <i
                                    data-lucide="circle-check-big"
                                    class="h-4 w-4"
                                ></i>

                                Terima
                            </button>

                            <button
                                type="button"
                                @click="openAction('REJECTED')"
                                class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                            >
                                <i
                                    data-lucide="circle-x"
                                    class="h-4 w-4"
                                ></i>

                                Tolak
                            </button>

                            <button
                                type="button"
                                @click="openAction('WITHDRAWN')"
                                class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100"
                            >
                                <i
                                    data-lucide="log-out"
                                    class="h-4 w-4"
                                ></i>

                                Mengundurkan Diri
                            </button>

                        </div>

                    @elseif ($registration->status === 'ACCEPTED')

                        @php
                            $requiredFee = (int) (
                                $registration->period?->default_reenroll_fee ?? 0
                            );

                            $totalPaid = (int) $registration
                                ->reenrollmentPayments
                                ->sum('amount');

                            $remaining = max(
                                0,
                                $requiredFee - $totalPaid
                            );
                        @endphp

                        <div
                            x-data="{
                                paymentOpen: false,
                                amountDisplay: '',
                                amountRaw: '',

                                formatAmount(value) {
                                    const numeric = String(value || '')
                                        .replace(/\D/g, '');

                                    this.amountRaw = numeric;

                                    this.amountDisplay = numeric
                                        ? new Intl.NumberFormat('id-ID').format(numeric)
                                        : '';
                                },

                                useRemaining() {
                                    this.formatAmount(@js((string) $remaining));
                                }
                            }"
                            class="flex w-full flex-col gap-4"
                        >

                            <div class="grid gap-3 sm:grid-cols-3">

                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Biaya Daftar Ulang
                                    </div>

                                    <div class="mt-1 text-lg font-bold text-slate-900">
                                        Rp {{ number_format($requiredFee, 0, ',', '.') }}
                                    </div>
                                </div>

                                <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-blue-500">
                                        Sudah Dibayar
                                    </div>

                                    <div class="mt-1 text-lg font-bold text-blue-900">
                                        Rp {{ number_format($totalPaid, 0, ',', '.') }}
                                    </div>
                                </div>

                                <div class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-3">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-violet-500">
                                        Sisa Tagihan
                                    </div>

                                    <div class="mt-1 text-lg font-bold text-violet-900">
                                        Rp {{ number_format($remaining, 0, ',', '.') }}
                                    </div>
                                </div>

                            </div>

                            <div class="flex flex-wrap gap-3">

                               @if (
                                    auth()->user()?->hasRole(
                                        'SUPERADMIN',
                                        'ADMIN',
                                        'BENDAHARA'
                                    )
                                )
                                    <button
                                        type="button"
                                        @click="paymentOpen = true"
                                        style="display:inline-flex"
                                        class="items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-700"
                                    >
                                        <i
                                            data-lucide="wallet-cards"
                                            class="h-4 w-4"
                                        ></i>

                                        Input Pembayaran
                                    </button>
                                @endif

                                <button
                                    type="button"
                                    @click="openAction('WITHDRAWN')"
                                    class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100"
                                >
                                    <i
                                        data-lucide="log-out"
                                        class="h-4 w-4"
                                    ></i>

                                    Mengundurkan Diri
                                </button>

                            </div>

                            @if (
                                auth()->user()?->hasRole(
                                    'SUPERADMIN',
                                    'ADMIN',
                                    'BENDAHARA'
                                )
                            )

                            {{-- Modal Pembayaran --}}
                            <div
                                x-show="paymentOpen"
                                x-cloak
                                class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6"
                            >

                                <div
                                    class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]"
                                    @click="paymentOpen = false"
                                ></div>

                                <div
                                    x-show="paymentOpen"
                                    x-transition.opacity.scale.95
                                    @click.stop
                                    class="relative z-10 w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl"
                                >

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'admin.registrations.reenrollment-payments.store',
                                            [
                                                'registration' => $registration,
                                                'period_id' => $registration->period_id,
                                            ]
                                        ) }}"
                                    >
                                        @csrf

                                        <div class="border-b border-slate-100 px-6 py-5">

                                            <div class="flex items-start justify-between gap-4">

                                                <div>

                                                    <h3 class="text-lg font-bold text-slate-900">
                                                        Input Pembayaran Daftar Ulang
                                                    </h3>

                                                    <p class="mt-1 text-sm leading-6 text-slate-500">
                                                        Catat pembayaran daftar ulang
                                                        {{ $registration->full_name }}.
                                                    </p>

                                                </div>

                                                <button
                                                    type="button"
                                                    @click="paymentOpen = false"
                                                    class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                                >
                                                    <i
                                                        data-lucide="x"
                                                        class="h-5 w-5"
                                                    ></i>
                                                </button>

                                            </div>

                                        </div>

                                        <div class="space-y-5 p-6">

                                            <div class="grid gap-3 sm:grid-cols-3">

                                                <div class="rounded-xl bg-slate-50 px-3 py-3">
                                                    <div class="text-[11px] text-slate-400">
                                                        Total
                                                    </div>

                                                    <div class="mt-1 text-sm font-bold text-slate-800">
                                                        Rp {{ number_format($requiredFee, 0, ',', '.') }}
                                                    </div>
                                                </div>

                                                <div class="rounded-xl bg-blue-50 px-3 py-3">
                                                    <div class="text-[11px] text-blue-500">
                                                        Terbayar
                                                    </div>

                                                    <div class="mt-1 text-sm font-bold text-blue-800">
                                                        Rp {{ number_format($totalPaid, 0, ',', '.') }}
                                                    </div>
                                                </div>

                                                <div class="rounded-xl bg-violet-50 px-3 py-3">
                                                    <div class="text-[11px] text-violet-500">
                                                        Sisa
                                                    </div>

                                                    <div class="mt-1 text-sm font-bold text-violet-800">
                                                        Rp {{ number_format($remaining, 0, ',', '.') }}
                                                    </div>
                                                </div>

                                            </div>

                                            <div>

                                                <div class="mb-2 flex items-center justify-between gap-3">

                                                    <label
                                                        for="payment-amount-display"
                                                        class="text-sm font-semibold text-slate-700"
                                                    >
                                                        Nominal Pembayaran
                                                        <span class="text-red-500">*</span>
                                                    </label>

                                                    <button
                                                        type="button"
                                                        @click="useRemaining()"
                                                        class="text-xs font-semibold text-violet-600 hover:text-violet-700"
                                                    >
                                                        Isi sisa tagihan
                                                    </button>

                                                </div>

                                                <div class="relative">

                                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-500">
                                                        Rp
                                                    </span>

                                                    <input
                                                        id="payment-amount-display"
                                                        type="text"
                                                        inputmode="numeric"
                                                        x-model="amountDisplay"
                                                        @input="formatAmount($event.target.value)"
                                                        placeholder="0"
                                                        class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 text-sm font-semibold focus:border-violet-500 focus:ring-4 focus:ring-violet-100"
                                                        required
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="amount"
                                                        :value="amountRaw"
                                                    >

                                                </div>

                                                @error('amount')
                                                    <p class="mt-1.5 text-xs text-red-600">
                                                        {{ $message }}
                                                    </p>
                                                @enderror

                                            </div>

                                            <div>

                                                <label
                                                    for="payment_method"
                                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                                >
                                                    Metode Pembayaran
                                                </label>

                                                <select
                                                    id="payment_method"
                                                    name="payment_method"
                                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-violet-500 focus:ring-4 focus:ring-violet-100"
                                                >
                                                    <option value="">
                                                        Pilih metode
                                                    </option>

                                                    <option value="CASH">
                                                        Tunai
                                                    </option>

                                                    <option value="TRANSFER">
                                                        Transfer
                                                    </option>

                                                    <option value="QRIS">
                                                        QRIS
                                                    </option>

                                                    <option value="OTHER">
                                                        Lainnya
                                                    </option>
                                                </select>

                                                @error('payment_method')
                                                    <p class="mt-1.5 text-xs text-red-600">
                                                        {{ $message }}
                                                    </p>
                                                @enderror

                                            </div>

                                            <div>

                                                <label
                                                    for="reference_number"
                                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                                >
                                                    Nomor Referensi
                                                    <span class="font-normal text-slate-400">
                                                        (opsional)
                                                    </span>
                                                </label>

                                                <input
                                                    id="reference_number"
                                                    type="text"
                                                    name="reference_number"
                                                    maxlength="100"
                                                    placeholder="Nomor transfer / referensi transaksi"
                                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-violet-500 focus:ring-4 focus:ring-violet-100"
                                                >

                                            </div>

                                            <div>

                                                <label
                                                    for="payment_notes"
                                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                                >
                                                    Catatan
                                                    <span class="font-normal text-slate-400">
                                                        (opsional)
                                                    </span>
                                                </label>

                                                <textarea
                                                    id="payment_notes"
                                                    name="notes"
                                                    rows="3"
                                                    maxlength="1000"
                                                    placeholder="Catatan pembayaran..."
                                                    class="w-full resize-none rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-violet-500 focus:ring-4 focus:ring-violet-100"
                                                ></textarea>

                                            </div>

                                        </div>

                                        <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">

                                            <button
                                                type="button"
                                                @click="paymentOpen = false"
                                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                                            >
                                                Batal
                                            </button>

                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-700"
                                            >
                                                <i
                                                    data-lucide="save"
                                                    class="h-4 w-4"
                                                ></i>

                                                Simpan Pembayaran
                                            </button>

                                        </div>

                                    </form>

                                </div>

                            @endif

                            </div>

                        </div>

                        @elseif ($registration->status === 'REENROLLED')

                            <div class="flex flex-col gap-4">

                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                    <div class="text-sm font-semibold text-amber-800">
                                        Pendaftar sudah menyelesaikan daftar ulang.
                                    </div>

                                    <div class="mt-1 text-sm text-amber-700">
                                        Jika pendaftar mengundurkan diri, pembayaran daftar ulang
                                        yang telah diterima tetap tercatat dan tidak dapat dikembalikan.
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3">

                                    <button
                                        type="button"
                                        @click="openAction('WITHDRAWN')"
                                        class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100"
                                    >
                                        <i
                                            data-lucide="log-out"
                                            class="h-4 w-4"
                                        ></i>

                                        Mengundurkan Diri
                                    </button>

                                </div>

                            </div>

                    @endif

                </div>

                {{-- Modal Perubahan Status --}}
                <div
                    x-show="actionOpen"
                    x-cloak
                    class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6"
                >

                    <div
                        class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]"
                        @click="actionOpen = false"
                    ></div>

                    <div
                        x-show="actionOpen"
                        x-transition.opacity.scale.95
                        @click.stop
                        class="relative z-10 w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl"
                    >

                        <form
                            method="POST"
                            action="{{ route(
                                'admin.registrations.status.update',
                                [
                                    'registration' => $registration,
                                    'period_id' => $registration->period_id,
                                ]
                            ) }}"
                        >
                            @csrf
                            @method('PATCH')

                            <input
                                type="hidden"
                                name="status"
                                :value="targetStatus"
                            >

                            <div class="border-b border-slate-100 px-6 py-5">

                                <div class="flex items-start justify-between gap-4">

                                    <div>

                                        <h3
                                            class="text-lg font-bold text-slate-900"
                                            x-text="actionTitle"
                                        ></h3>

                                        <p
                                            class="mt-1 text-sm leading-6 text-slate-500"
                                            x-text="actionDescription"
                                        ></p>

                                    </div>

                                    <button
                                        type="button"
                                        @click="actionOpen = false"
                                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                    >
                                        <i
                                            data-lucide="x"
                                            class="h-5 w-5"
                                        ></i>
                                    </button>

                                </div>

                            </div>

                            <div class="p-6">

                                <div class="mb-5 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                                    <div class="flex items-start gap-3">

                                        <i
                                            data-lucide="circle-help"
                                            class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600"
                                        ></i>

                                        <p class="text-sm leading-5 text-emerald-800">
                                            Pastikan data pendaftar sudah diverifikasi sebelum memproses perubahan status.
                                        </p>

                                    </div>
                                </div>

                                <label
                                    for="status-notes"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Catatan
                                    <span class="font-normal text-slate-400">
                                        (opsional)
                                    </span>
                                </label>

                                <textarea
                                    id="status-notes"
                                    name="notes"
                                    rows="3"
                                    maxlength="1000"
                                    placeholder="Tambahkan catatan jika diperlukan..."
                                    class="w-full resize-none rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                                ></textarea>

                            </div>

                            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">

                                <button
                                    type="button"
                                    @click="actionOpen = false"
                                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                                >
                                    Batal
                                </button>

                                <button
                                    type="submit"
                                    :class="actionClass"
                                    class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition"
                                    x-text="actionButton"
                                ></button>

                            </div>

                        </form>

                    </div>

                </div>

            </section>

        @endif

        {{-- Primary --}}
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,0.7fr)]">

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h2 class="font-bold text-slate-900">
                        Identitas Calon Siswa
                    </h2>

                </div>

                <div class="grid gap-x-8 gap-y-5 p-6 sm:grid-cols-2">

                    @php
                        $identityFields = [
                            'NIK' => $registration->nik,
                            'NISN' => $registration->nisn,
                            'Nama Lengkap' => $registration->full_name,
                            'Jenis Kelamin' => match ($registration->gender) {
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                                default => $registration->gender,
                            },
                            'Tempat Lahir' => $registration->birth_place,
                            'Tanggal Lahir' => $registration->birth_date?->format('d/m/Y'),
                            'Agama' => $registration->religion,
                            'Asal Sekolah' => $registration->origin_school,
                        ];
                    @endphp

                    @foreach ($identityFields as $label => $value)

                        <div>

                            <div class="text-xs font-medium text-slate-400">
                                {{ $label }}
                            </div>

                            <div class="mt-1 text-sm font-semibold text-slate-800">
                                {{ filled($value) ? $value : '-' }}
                            </div>

                        </div>

                    @endforeach

                </div>

            </section>

            {{-- Registration --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h2 class="font-bold text-slate-900">
                        Informasi Pendaftaran
                    </h2>

                </div>

                <div class="space-y-5 p-6">

                    <div>
                        <div class="text-xs text-slate-400">
                            Periode
                        </div>

                        <div class="mt-1 font-semibold text-slate-800">
                            {{ $registration->period?->name ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-400">
                            Jalur
                        </div>

                        <div class="mt-1 font-semibold text-slate-800">
                            {{ $registration->admissionPath?->name ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-400">
                            Jurusan
                        </div>

                        <div class="mt-1 font-semibold text-slate-800">
                            {{ $registration->major?->code ?? '-' }}
                        </div>

                        @if ($registration->major)
                            <div class="mt-1 text-xs leading-5 text-slate-400">
                                {{ $registration->major->name }}
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="text-xs text-slate-400">
                            Sumber Data
                        </div>

                        <div class="mt-1 font-semibold text-slate-800">
                            {{ $registration->data_source === 'PUBLIC'
                                ? 'Pendaftaran Online'
                                : 'Input Admin' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-400">
                            Tanggal Daftar
                        </div>

                        <div class="mt-1 font-semibold text-slate-800">
                            {{ $registration->registered_at?->format('d/m/Y H:i') ?? '-' }}
                        </div>
                    </div>

                </div>

            </section>

        </div>

        {{-- Address + Parent --}}
        <div class="grid gap-6 lg:grid-cols-2">

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="font-bold text-slate-900">
                        Alamat
                    </h2>
                </div>

                <div class="grid gap-5 p-6 sm:grid-cols-2">

                    @foreach ([
                        'Dusun' => $registration->hamlet,
                        'RT' => $registration->rt,
                        'RW' => $registration->rw,
                        'Desa / Kelurahan' => $registration->village,
                        'Kecamatan' => $registration->district,
                        'Kabupaten / Kota' => $registration->city,
                        'Provinsi' => $registration->province,
                        'Kode Pos' => $registration->postal_code,
                    ] as $label => $value)

                        <div>
                            <div class="text-xs text-slate-400">
                                {{ $label }}
                            </div>

                            <div class="mt-1 text-sm font-semibold text-slate-800">
                                {{ filled($value) ? $value : '-' }}
                            </div>
                        </div>

                    @endforeach

                </div>

            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="font-bold text-slate-900">
                        Orang Tua & Kontak
                    </h2>
                </div>

                <div class="grid gap-5 p-6 sm:grid-cols-2">

                    @foreach ([
                        'Nama Ayah' => $registration->father_name,
                        'Pekerjaan Ayah' => $registration->father_job,
                        'Nama Ibu' => $registration->mother_name,
                        'Pekerjaan Ibu' => $registration->mother_job,
                        'WhatsApp' => $registration->whatsapp,
                    ] as $label => $value)

                        <div>
                            <div class="text-xs text-slate-400">
                                {{ $label }}
                            </div>

                            <div class="mt-1 text-sm font-semibold text-slate-800">
                                {{ filled($value) ? $value : '-' }}
                            </div>
                        </div>

                    @endforeach

                </div>

            </section>

        </div>

        {{-- Options --}}
        <div class="grid gap-6 lg:grid-cols-2">

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="font-bold text-slate-900">
                    Keringanan / Prestasi
                </h2>

                @if ($registration->reliefOptions->isEmpty())

                    <p class="mt-3 text-sm text-slate-500">
                        Tidak ada pilihan.
                    </p>

                @else

                    <div class="mt-4 flex flex-wrap gap-2">

                        @foreach ($registration->reliefOptions as $option)

                            <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                                {{ $option->name }}
                            </span>

                        @endforeach

                    </div>

                @endif

            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="font-bold text-slate-900">
                    Program Khusus
                </h2>

                @if ($registration->specialPrograms->isEmpty())

                    <p class="mt-3 text-sm text-slate-500">
                        Tidak ada pilihan.
                    </p>

                @else

                    <div class="mt-4 flex flex-wrap gap-2">

                        @foreach ($registration->specialPrograms as $program)

                            <span class="rounded-full bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700">
                                {{ $program->name }}
                            </span>

                        @endforeach

                    </div>

                @endif

            </section>

        </div>

        {{-- Riwayat Pembayaran Daftar Ulang --}}
        @if (
            in_array(
                $registration->status,
                ['ACCEPTED', 'REENROLLED'],
                true
            )
            || $registration->reenrollmentPayments->isNotEmpty()
        )

            @php
                $paymentRequiredFee = (int) (
                    $registration->period?->default_reenroll_fee ?? 0
                );

                $paymentTotalPaid = (int) $registration
                    ->reenrollmentPayments
                    ->sum('amount');

                $paymentRemaining = max(
                    0,
                    $paymentRequiredFee - $paymentTotalPaid
                );
            @endphp

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h2 class="font-bold text-slate-900">
                            Pembayaran Daftar Ulang
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Riwayat transaksi pembayaran daftar ulang.
                        </p>
                    </div>

                    <div class="text-right">

                        <div class="text-xs text-slate-400">
                            Sisa Tagihan
                        </div>

                        <div class="mt-1 font-bold {{ $paymentRemaining === 0 ? 'text-emerald-600' : 'text-violet-700' }}">
                            Rp {{ number_format($paymentRemaining, 0, ',', '.') }}
                        </div>

                    </div>

                </div>

                @if ($registration->reenrollmentPayments->isEmpty())

                    <div class="p-6 text-sm text-slate-500">
                        Belum ada pembayaran daftar ulang.
                    </div>

                @else

                    <div class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-slate-200">

                            <thead class="bg-slate-50">

                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <th class="px-6 py-3.5">
                                        Tanggal
                                    </th>

                                    <th class="px-6 py-3.5">
                                        Nominal
                                    </th>

                                    <th class="px-6 py-3.5">
                                        Metode
                                    </th>

                                    <th class="px-6 py-3.5">
                                        Referensi
                                    </th>

                                    <th class="px-6 py-3.5">
                                        Catatan
                                    </th>

                                    @if (
                                        auth()->user()?->hasRole(
                                            'SUPERADMIN',
                                            'ADMIN',
                                            'BENDAHARA'
                                        )
                                    )
                                        <th class="px-6 py-3.5 text-right">
                                            Bukti
                                        </th>
                                    @endif
                                </tr>

                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">

                                @foreach (
                                    $registration->reenrollmentPayments
                                        ->sortByDesc('paid_at')
                                    as $payment
                                )

                                    <tr>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                            {{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-slate-900">
                                            Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ match ($payment->payment_method) {
                                                'CASH' => 'Tunai',
                                                'TRANSFER' => 'Transfer',
                                                'QRIS' => 'QRIS',
                                                'OTHER' => 'Lainnya',
                                                default => $payment->payment_method ?? '-',
                                            } }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $payment->reference_number ?: '-' }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-slate-500">
                                            {{ $payment->notes ?: '-' }}
                                        </td>

                                        @if (
                                            auth()->user()?->hasRole(
                                                'SUPERADMIN',
                                                'ADMIN',
                                                'BENDAHARA'
                                            )
                                        )
                                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                                <a
                                                    href="{{ route(
                                                        'admin.registrations.reenrollment-payments.receipt',
                                                        [
                                                            'registration' => $registration,
                                                            'payment' => $payment,
                                                        ]
                                                    ) }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                                >
                                                    <i
                                                        data-lucide="printer"
                                                        class="h-4 w-4"
                                                    ></i>

                                                    Cetak Bukti
                                                </a>
                                            </td>
                                        @endif

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @endif

            </section>

        @endif

        {{-- History --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 px-6 py-5">

                <h2 class="font-bold text-slate-900">
                    Riwayat Status
                </h2>

            </div>

            @if ($registration->statusHistories->isEmpty())

                <div class="p-6 text-sm text-slate-500">
                    Belum ada riwayat.
                </div>

            @else

                <div class="divide-y divide-slate-100">

                    @foreach ($registration->statusHistories as $history)

                        @php
                            $toLabel = match ($history->to_status) {
                                'REGISTERED' => 'Terdaftar',
                                'ACCEPTED' => 'Diterima',
                                'REJECTED' => 'Ditolak',
                                'REENROLLED' => 'Daftar Ulang',
                                'WITHDRAWN' => 'Mengundurkan Diri',
                                default => $history->to_status,
                            };
                        @endphp

                        <div class="flex items-start gap-4 px-6 py-4">

                            <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></div>

                            <div>

                                <div class="text-sm font-semibold text-slate-800">
                                    {{ $toLabel }}
                                </div>

                                <div class="mt-1 text-xs text-slate-400">
                                    {{ $history->changed_at?->format('d/m/Y H:i') }}
                                </div>

                                @if ($history->notes)
                                    <div class="mt-2 text-sm text-slate-500">
                                        {{ $history->notes }}
                                    </div>
                                @endif

                            </div>

                        </div>

                    @endforeach

                </div>

            @endif

        </section>

    </div>
@endsection
