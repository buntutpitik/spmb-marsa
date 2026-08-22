<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login | {{ config('app.name') }}
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body class="min-h-screen bg-slate-50 text-slate-800">

    <main class="flex min-h-screen items-center justify-center px-4 py-10">

        <div class="w-full max-w-md">

            <div class="mb-8 text-center">

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600 text-lg font-bold text-white shadow-sm">
                    SM
                </div>

                <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">
                    SPMB MARSA
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    Masuk ke panel pengelolaan SPMB.
                </p>

            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">

                <div class="mb-6">
                    <h2 class="text-xl font-bold text-slate-900">
                        Login
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Gunakan akun Administrator yang telah terdaftar.
                    </p>
                </div>

                @if ($errors->any())

                    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4">

                        <div class="flex items-start gap-3">

                            <i
                                data-lucide="circle-alert"
                                class="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                            ></i>

                            <div class="text-sm text-red-700">
                                {{ $errors->first() }}
                            </div>

                        </div>

                    </div>

                @endif

                <form
                    method="POST"
                    action="{{ route('login.store') }}"
                    class="space-y-5"
                >
                    @csrf

                    <div>

                        <label
                            for="email"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Email
                        </label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="admin@example.com"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                        >

                    </div>

                    <div>

                        <label
                            for="password"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Password
                        </label>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                        >

                    </div>

                    <label class="flex cursor-pointer items-center gap-3">

                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        >

                        <span class="text-sm text-slate-600">
                            Ingat saya
                        </span>

                    </label>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200"
                    >
                        <i
                            data-lucide="log-in"
                            class="h-4 w-4"
                        ></i>

                        Masuk
                    </button>

                </form>

            </section>

            <p class="mt-6 text-center text-xs text-slate-400">
                Panel internal {{ config('app.name') }}
            </p>

        </div>

    </main>

</body>
</html>