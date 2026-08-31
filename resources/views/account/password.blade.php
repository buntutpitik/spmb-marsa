@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h1 class="text-xl font-semibold text-slate-900">
                    Ubah Password
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Gunakan password saat ini untuk mengamankan perubahan password akun Anda.
                </p>
            </div>

            @if (session('success'))
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('account.password.update') }}"
                class="space-y-5"
            >
                @csrf
                @method('PUT')

                <div>
                    <label
                        for="current_password"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Password Saat Ini
                    </label>

                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >

                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="password"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Password Baru
                    </label>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >

                    @error('password')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="password_confirmation"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Konfirmasi Password Baru
                    </label>

                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >
                </div>

                <div class="flex justify-end gap-3">
                    <a
                        href="{{ route('dashboard') }}"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                    >
                        Simpan Password
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection