<header
    x-data="{ userMenuOpen: false }"
    class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur"
>
    <div class="flex h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

        {{-- Left --}}
        <div class="flex min-w-0 items-center gap-3">

            <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 shadow-sm hover:bg-slate-50 lg:hidden"
                @click="sidebarOpen = true"
            >
                <span class="sr-only">
                    Buka sidebar
                </span>

                <i
                    data-lucide="menu"
                    class="h-5 w-5"
                ></i>
            </button>

            <div class="min-w-0">

                <div class="text-xs font-medium text-slate-500">
                    Sistem SPMB
                </div>

                <div class="truncate text-lg font-bold tracking-tight text-slate-900">
                    {{ $pageTitle ?? 'Dashboard' }}
                </div>

            </div>

        </div>

        {{-- Right --}}
        <div class="flex items-center gap-3">

            {{-- Period --}}
            @if ($activePeriod)
                <div class="hidden rounded-xl bg-slate-50 px-3 py-2 lg:block">

                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                        Periode Aktif
                    </div>

                    <div class="mt-0.5 flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                        {{ $activePeriod->name }}
                    </div>

                </div>
            @endif

            {{-- Notification --}}
            <button
                type="button"
                class="relative flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50"
            >
                <i
                    data-lucide="bell"
                    class="h-5 w-5"
                ></i>

                <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
            </button>

            <div class="hidden h-9 w-px bg-slate-200 sm:block"></div>

            {{-- User Menu --}}
            @auth

                @php
                    $user = auth()->user();

                    $initials = collect(
                        preg_split(
                            '/\s+/',
                            trim($user->name)
                        )
                    )
                        ->filter()
                        ->take(2)
                        ->map(
                            fn ($part) => mb_strtoupper(
                                mb_substr($part, 0, 1)
                            )
                        )
                        ->implode('');

                    $roleLabel = match ($user->role) {
                        'SUPERADMIN' => 'Superadmin',
                        'ADMIN' => 'Administrator',
                        default => $user->role,
                    };
                @endphp

                <div class="relative">

                    <button
                        type="button"
                        @click="userMenuOpen = ! userMenuOpen"
                        class="flex items-center gap-3 rounded-xl p-1.5 pr-3 transition hover:bg-slate-50"
                    >

                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-sm font-bold text-white">
                            {{ $initials }}
                        </div>

                        <div class="hidden max-w-44 text-left sm:block">

                            <div class="truncate text-sm font-semibold text-slate-800">
                                {{ $user->name }}
                            </div>

                            <div class="text-xs text-slate-500">
                                {{ $roleLabel }}
                            </div>

                        </div>

                        <i
                            data-lucide="chevron-down"
                            class="hidden h-4 w-4 text-slate-400 transition sm:block"
                            :class="userMenuOpen ? 'rotate-180' : ''"
                        ></i>

                    </button>

                    {{-- Dropdown --}}
                    <div
                        x-show="userMenuOpen"
                        x-transition
                        @click.outside="userMenuOpen = false"
                        class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg"
                        style="display: none;"
                    >

                        <div class="border-b border-slate-100 px-4 py-4">

                            <div class="text-sm font-semibold text-slate-900">
                                {{ $user->name }}
                            </div>

                            <div class="mt-1 truncate text-xs text-slate-500">
                                {{ $user->email }}
                            </div>

                            <div class="mt-2">
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                                    {{ $roleLabel }}
                                </span>
                            </div>

                        </div>

                        <div class="p-2">

                            <a
                                href="{{ route('account.password.edit') }}"
                                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
                                    <i
                                        data-lucide="key-round"
                                        class="h-4 w-4"
                                    ></i>
                                </span>

                                Ubah Password
                            </a>

                            <div class="my-1 border-t border-slate-100"></div>

                            <form
                                method="POST"
                                action="{{ route('logout') }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-red-600 transition hover:bg-red-50"
                                >
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50">
                                        <i
                                            data-lucide="log-out"
                                            class="h-4 w-4"
                                        ></i>
                                    </span>

                                    Keluar
                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            @endauth

        </div>

    </div>
</header>