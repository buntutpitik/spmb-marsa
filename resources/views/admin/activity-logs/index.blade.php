@extends('layouts.app')

@section('content')
    <div class="space-y-8">

        @php
            $actionLabels = [
                'CREATE_REGISTRATION' => 'Pendaftaran Dibuat',
                'CHANGE_STATUS' => 'Perubahan Status',
                'REENROLLMENT_PAYMENT' => 'Pembayaran Daftar Ulang',
                'CREATE_USER' => 'User Dibuat',
                'UPDATE_USER' => 'User Diperbarui',
                'TOGGLE_USER_ACTIVE' => 'Status User Diubah',
                'RESET_USER_PASSWORD' => 'Password User Direset',
            ];
        @endphp

        {{-- Heading --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">
                    Sistem
                </p>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Activity Log
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Pantau riwayat aktivitas penting pada panel SPMB secara read-only.
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                    Total Ditampilkan
                </div>

                <div class="mt-1 text-sm font-semibold text-slate-800">
                    {{ number_format($logs->total()) }} aktivitas
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <form
                method="GET"
                action="{{ route('admin.activity-logs.index') }}"
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
                                value="{{ $search }}"
                                placeholder="Deskripsi, action, actor, nama / nomor pendaftaran..."
                                class="w-full rounded-xl border border-slate-300 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:w-[520px]">

                        <div>
                            <label
                                for="action"
                                class="mb-1.5 block text-xs font-semibold text-slate-600"
                            >
                                Action
                            </label>

                            <select
                                id="action"
                                name="action"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                                <option value="">
                                    Semua Action
                                </option>

                                @foreach ($actions as $action)
                                    <option
                                        value="{{ $action }}"
                                        @selected($selectedAction === $action)
                                    >
                                        {{ $actionLabels[$action] ?? ucwords(strtolower(str_replace('_', ' ', $action))) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label
                                for="user_id"
                                class="mb-1.5 block text-xs font-semibold text-slate-600"
                            >
                                Actor
                            </label>

                            <select
                                id="user_id"
                                name="user_id"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                                <option value="">
                                    Semua Actor
                                </option>

                                @foreach ($users as $user)
                                    <option
                                        value="{{ $user->id }}"
                                        @selected((string) $selectedUserId === (string) $user->id)
                                    >
                                        {{ $user->name }} — {{ $user->role }}
                                    </option>
                                @endforeach
                            </select>
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
                            href="{{ route('admin.activity-logs.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </section>

        {{-- Logs --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="font-bold text-slate-900">
                    Riwayat Aktivitas
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Data hanya dapat dilihat dan tidak dapat diedit atau dihapus dari halaman ini.
                </p>
            </div>

            @if ($logs->isEmpty())
                <div class="flex min-h-[260px] items-center justify-center p-6">
                    <div class="text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                            <i
                                data-lucide="list-checks"
                                class="h-5 w-5"
                            ></i>
                        </div>

                        <p class="mt-4 text-sm font-semibold text-slate-700">
                            Belum ada aktivitas
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Riwayat aktivitas akan muncul di sini.
                        </p>
                    </div>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($logs as $log)
                        @php
                            $metadata = is_array($log->metadata)
                                ? $log->metadata
                                : [];

                            $targetName = $log->registration?->full_name
                                ?? ($metadata['target_name'] ?? null);

                            $targetDetail = $log->registration?->registration_number
                                ?? ($metadata['target_email'] ?? null);

                            $label = $actionLabels[$log->action]
                                ?? ucwords(
                                    strtolower(
                                        str_replace('_', ' ', $log->action)
                                    )
                                );

                            $badgeClass = match ($log->action) {
                                'CREATE_REGISTRATION', 'CREATE_USER' =>
                                    'bg-emerald-50 text-emerald-700',

                                'CHANGE_STATUS', 'UPDATE_USER' =>
                                    'bg-blue-50 text-blue-700',

                                'REENROLLMENT_PAYMENT' =>
                                    'bg-violet-50 text-violet-700',

                                'TOGGLE_USER_ACTIVE' =>
                                    'bg-amber-50 text-amber-700',

                                'RESET_USER_PASSWORD' =>
                                    'bg-slate-100 text-slate-700',

                                default =>
                                    'bg-slate-100 text-slate-600',
                            };
                        @endphp

                        <article class="px-6 py-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}">
                                            {{ $label }}
                                        </span>

                                        <span class="text-xs text-slate-400">
                                            {{ $log->created_at?->format('d/m/Y H:i:s') ?? '-' }}
                                        </span>
                                    </div>

                                    <p class="mt-3 text-sm font-semibold leading-6 text-slate-900">
                                        {{ $log->description ?: '-' }}
                                    </p>

                                    <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-4">

                                        <div>
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                Actor
                                            </div>

                                            <div class="mt-1 font-medium text-slate-700">
                                                {{ $log->user?->name ?? 'Sistem / Tidak diketahui' }}
                                            </div>

                                            @if ($log->user?->email)
                                                <div class="mt-0.5 text-xs text-slate-400">
                                                    {{ $log->user->email }}
                                                </div>
                                            @endif
                                        </div>

                                        <div>
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                Target
                                            </div>

                                            <div class="mt-1 font-medium text-slate-700">
                                                {{ $targetName ?: '-' }}
                                            </div>

                                            @if ($targetDetail)
                                                <div class="mt-0.5 text-xs text-slate-400">
                                                    {{ $targetDetail }}
                                                </div>
                                            @endif
                                        </div>

                                        <div>
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                Action
                                            </div>

                                            <div class="mt-1 font-mono text-xs font-semibold text-slate-600">
                                                {{ $log->action }}
                                            </div>
                                        </div>

                                        <div>
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                IP Address
                                            </div>

                                            <div class="mt-1 font-medium text-slate-700">
                                                {{ $log->ip_address ?: '-' }}
                                            </div>
                                        </div>

                                    </div>

                                    @if (! empty($metadata))
                                        <details class="mt-4 rounded-xl border border-slate-200 bg-slate-50">
                                            <summary class="cursor-pointer px-4 py-3 text-xs font-semibold text-slate-600">
                                                Detail Metadata
                                            </summary>

                                            <div class="border-t border-slate-200 px-4 py-3">
                                                <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 xl:grid-cols-3">
                                                    @foreach ($metadata as $key => $value)
                                                        @if (! in_array($key, ['target_name', 'target_email'], true))
                                                            <div>
                                                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                                    {{ str_replace('_', ' ', $key) }}
                                                                </dt>

                                                                <dd class="mt-1 break-words text-xs text-slate-600">
                                                                    @if (is_array($value))
                                                                        {{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                                                                    @elseif (is_bool($value))
                                                                        {{ $value ? 'true' : 'false' }}
                                                                    @elseif (is_null($value))
                                                                        -
                                                                    @else
                                                                        {{ $value }}
                                                                    @endif
                                                                </dd>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </dl>
                                            </div>
                                        </details>
                                    @endif
                                </div>

                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($logs->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $logs->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
