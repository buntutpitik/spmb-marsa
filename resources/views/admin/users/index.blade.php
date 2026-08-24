@extends('layouts.app')

@section('content')
    <div
        x-data="{
            createOpen: false,
            editOpen: false,
            passwordOpen: false,
            selectedUser: null,

            openEdit(user) {
                this.selectedUser = user;
                this.editOpen = true;
            },

            openPassword(user) {
                this.selectedUser = user;
                this.passwordOpen = true;
            }
        }"
        class="space-y-8"
    >
        {{-- Heading --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-600">
                    Sistem
                </p>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Users
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Kelola akun internal, role, status akses, dan password pengguna panel SPMB.
                </p>
            </div>

            <button
                type="button"
                @click="createOpen = true"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
            >
                <i
                    data-lucide="user-plus"
                    class="h-4 w-4"
                ></i>

                Tambah User
            </button>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <div class="font-semibold">
                    Terdapat data yang perlu diperbaiki.
                </div>

                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Summary --}}
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Total User
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            {{ number_format($summary['total']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                        <i
                            data-lucide="users"
                            class="h-5 w-5"
                        ></i>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Aktif
                        </p>

                        <p class="mt-2 text-2xl font-bold text-emerald-600">
                            {{ number_format($summary['active']) }}
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

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Nonaktif
                        </p>

                        <p class="mt-2 text-2xl font-bold text-rose-600">
                            {{ number_format($summary['inactive']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                        <i
                            data-lucide="user-x"
                            class="h-5 w-5"
                        ></i>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Superadmin Aktif
                        </p>

                        <p class="mt-2 text-2xl font-bold text-violet-600">
                            {{ number_format($summary['superadmin']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        <i
                            data-lucide="shield-check"
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
                action="{{ route('admin.users.index') }}"
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
                                placeholder="Nama atau email user..."
                                class="w-full rounded-xl border border-slate-300 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:w-[420px]">
                        <div>
                            <label
                                for="role"
                                class="mb-1.5 block text-xs font-semibold text-slate-600"
                            >
                                Role
                            </label>

                            <select
                                id="role"
                                name="role"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                                <option value="">
                                    Semua Role
                                </option>

                                @foreach ($roles as $value => $label)
                                    <option
                                        value="{{ $value }}"
                                        @selected(request('role') === $value)
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

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
                                <option value="ACTIVE" @selected(request('status') === 'ACTIVE')>
                                    Aktif
                                </option>
                                <option value="INACTIVE" @selected(request('status') === 'INACTIVE')>
                                    Nonaktif
                                </option>
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
                            href="{{ route('admin.users.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </section>

        {{-- Table --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="font-bold text-slate-900">
                    Daftar User
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Hanya SUPERADMIN yang dapat mengelola akun internal.
                </p>
            </div>

            @if ($users->isEmpty())
                <div class="p-8 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i
                            data-lucide="users"
                            class="h-5 w-5"
                        ></i>
                    </div>

                    <p class="mt-4 text-sm font-semibold text-slate-700">
                        Tidak ada user ditemukan.
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Coba ubah filter atau tambahkan user baru.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3.5">
                                    User
                                </th>
                                <th class="px-6 py-3.5">
                                    Role
                                </th>
                                <th class="px-6 py-3.5">
                                    Status
                                </th>
                                <th class="px-6 py-3.5">
                                    Terdaftar
                                </th>
                                <th class="px-6 py-3.5 text-right">
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($users as $user)
                                @php
                                    $roleClass = match ($user->role) {
                                        'SUPERADMIN' => 'bg-violet-50 text-violet-700',
                                        'ADMIN' => 'bg-blue-50 text-blue-700',
                                        'PANITIA' => 'bg-amber-50 text-amber-700',
                                        'BENDAHARA' => 'bg-emerald-50 text-emerald-700',
                                        default => 'bg-slate-100 text-slate-600',
                                    };

                                    $roleLabel = $roles[$user->role] ?? $user->role;
                                @endphp

                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-slate-600">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>

                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="truncate text-sm font-semibold text-slate-900">
                                                        {{ $user->name }}
                                                    </p>

                                                    @if (auth()->id() === $user->id)
                                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                                            Anda
                                                        </span>
                                                    @endif
                                                </div>

                                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                                    {{ $user->email }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $roleClass }}">
                                            {{ $roleLabel }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        @if ($user->is_active)
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
                                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-700">
                                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                        {{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button
                                                type="button"
                                                @click="openEdit(@js([
                                                    'id' => $user->id,
                                                    'name' => $user->name,
                                                    'email' => $user->email,
                                                    'role' => $user->role,
                                                    'is_active' => $user->is_active,
                                                    'is_self' => auth()->id() === $user->id,
                                                ]))"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                                            >
                                                <i
                                                    data-lucide="pencil"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                                Edit
                                            </button>

                                            <button
                                                type="button"
                                                @click="openPassword(@js([
                                                    'id' => $user->id,
                                                    'name' => $user->name,
                                                    'email' => $user->email,
                                                ]))"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                                            >
                                                <i
                                                    data-lucide="key-round"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                                Reset Password
                                            </button>

                                            @if (auth()->id() !== $user->id)
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.users.toggle-active', $user) }}"
                                                    onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan user ini?' : 'Aktifkan user ini?' }}')"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        type="submit"
                                                        class="{{ $user->is_active
                                                            ? 'inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100'
                                                            : 'inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100' }}"
                                                    >
                                                        <i
                                                            data-lucide="{{ $user->is_active ? 'user-x' : 'user-check' }}"
                                                            class="h-3.5 w-3.5"
                                                        ></i>

                                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $users->links() }}
                    </div>
                @endif
            @endif
        </section>

        {{-- Create Modal --}}
        <div
            x-show="createOpen"
            x-cloak
            class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6"
        >
            <div
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]"
                @click="createOpen = false"
            ></div>

            <div
                x-show="createOpen"
                x-transition.opacity.scale.95
                @click.stop
                class="relative z-10 w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <form
                    method="POST"
                    action="{{ route('admin.users.store') }}"
                >
                    @csrf

                    <div class="border-b border-slate-100 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">
                                    Tambah User
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Buat akun internal baru untuk panel SPMB.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="createOpen = false"
                                class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            >
                                <i
                                    data-lucide="x"
                                    class="h-5 w-5"
                                ></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <div>
                            <label
                                for="create_name"
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Nama
                            </label>

                            <input
                                id="create_name"
                                type="text"
                                name="name"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                        </div>

                        <div>
                            <label
                                for="create_email"
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Email
                            </label>

                            <input
                                id="create_email"
                                type="email"
                                name="email"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                        </div>

                        <div>
                            <label
                                for="create_role"
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Role
                            </label>

                            <select
                                id="create_role"
                                name="role"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                                @foreach ($roles as $value => $label)
                                    <option value="{{ $value }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    for="create_password"
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                >
                                    Password
                                </label>

                                <input
                                    id="create_password"
                                    type="password"
                                    name="password"
                                    minlength="8"
                                    required
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                                >
                            </div>

                            <div>
                                <label
                                    for="create_password_confirmation"
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                >
                                    Konfirmasi Password
                                </label>

                                <input
                                    id="create_password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    minlength="8"
                                    required
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                        <button
                            type="button"
                            @click="createOpen = false"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            <i
                                data-lucide="save"
                                class="h-4 w-4"
                            ></i>

                            Simpan User
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Modal --}}
        <div
            x-show="editOpen"
            x-cloak
            class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6"
        >
            <div
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]"
                @click="editOpen = false"
            ></div>

            <div
                x-show="editOpen"
                x-transition.opacity.scale.95
                @click.stop
                class="relative z-10 w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <form
                    method="POST"
                    :action="selectedUser ? '{{ url('/admin/users') }}/' + selectedUser.id : '#'"
                >
                    @csrf
                    @method('PUT')

                    <div class="border-b border-slate-100 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">
                                    Edit User
                                </h3>

                                <p
                                    class="mt-1 text-sm text-slate-500"
                                    x-text="selectedUser ? selectedUser.email : ''"
                                ></p>
                            </div>

                            <button
                                type="button"
                                @click="editOpen = false"
                                class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            >
                                <i
                                    data-lucide="x"
                                    class="h-5 w-5"
                                ></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <div>
                            <label
                                for="edit_name"
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Nama
                            </label>

                            <input
                                id="edit_name"
                                type="text"
                                name="name"
                                x-model="selectedUser.name"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                        </div>

                        <div>
                            <label
                                for="edit_email"
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Email
                            </label>

                            <input
                                id="edit_email"
                                type="email"
                                name="email"
                                x-model="selectedUser.email"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                        </div>

                        <div>
                            <label
                                for="edit_role"
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Role
                            </label>

                            <select
                                id="edit_role"
                                name="role"
                                x-model="selectedUser.role"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                                @foreach ($roles as $value => $label)
                                    <option value="{{ $value }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            <p
                                x-show="selectedUser && selectedUser.is_self"
                                class="mt-1.5 text-xs text-amber-600"
                            >
                                Role akun sendiri tidak dapat diturunkan dari SUPERADMIN.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                        <button
                            type="button"
                            @click="editOpen = false"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            <i
                                data-lucide="save"
                                class="h-4 w-4"
                            ></i>

                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Reset Password Modal --}}
        <div
            x-show="passwordOpen"
            x-cloak
            class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto p-4 sm:p-6"
        >
            <div
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]"
                @click="passwordOpen = false"
            ></div>

            <div
                x-show="passwordOpen"
                x-transition.opacity.scale.95
                @click.stop
                class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <form
                    method="POST"
                    :action="selectedUser ? '{{ url('/admin/users') }}/' + selectedUser.id + '/reset-password' : '#'"
                >
                    @csrf
                    @method('PATCH')

                    <div class="border-b border-slate-100 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">
                                    Reset Password
                                </h3>

                                <p
                                    class="mt-1 text-sm text-slate-500"
                                    x-text="selectedUser ? selectedUser.name : ''"
                                ></p>
                            </div>

                            <button
                                type="button"
                                @click="passwordOpen = false"
                                class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            >
                                <i
                                    data-lucide="x"
                                    class="h-5 w-5"
                                ></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <div>
                            <label
                                for="reset_password"
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Password Baru
                            </label>

                            <input
                                id="reset_password"
                                type="password"
                                name="password"
                                minlength="8"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                        </div>

                        <div>
                            <label
                                for="reset_password_confirmation"
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Konfirmasi Password
                            </label>

                            <input
                                id="reset_password_confirmation"
                                type="password"
                                name="password_confirmation"
                                minlength="8"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            >
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                        <button
                            type="button"
                            @click="passwordOpen = false"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            <i
                                data-lucide="key-round"
                                class="h-4 w-4"
                            ></i>

                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
