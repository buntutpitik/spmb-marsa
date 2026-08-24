<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-300 lg:translate-x-0"
>
    <div class="flex h-20 items-center justify-between border-b border-slate-100 px-6">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-lg font-bold text-white shadow-sm">
                SM
            </div>

            <div>
                <div class="text-base font-bold tracking-tight text-slate-900">
                    SPMB MARSA
                </div>

                <div class="text-xs text-slate-500">
                    Sistem Penerimaan Murid Baru
                </div>
            </div>
        </a>

        <button
            type="button"
            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
            @click="sidebarOpen = false"
        >
            <span class="sr-only">
                Tutup sidebar
            </span>

            <i
                data-lucide="x"
                class="h-5 w-5"
            ></i>
        </button>
    </div>

    <div class="sidebar-scroll flex-1 overflow-y-auto px-4 py-6">
        <nav class="space-y-7">

            {{-- MAIN --}}
            <div>
                <div class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                    Main
                </div>

                <a
                    href="{{ route('dashboard') }}"
                    class="{{ request()->routeIs('dashboard')
                        ? 'flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700'
                        : 'sidebar-link' }}"
                >
                    <span
                        class="{{ request()->routeIs('dashboard')
                            ? 'flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100'
                            : 'sidebar-icon' }}"
                    >
                        <i
                            data-lucide="layout-dashboard"
                            class="h-4 w-4"
                        ></i>
                    </span>

                    Dashboard
                </a>
            </div>

            {{-- SPMB --}}
            <div>
                <div class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                    SPMB
                </div>

                <div class="space-y-1">

                    <a
                        href="{{ route('admin.registrations.index') }}"
                        class="{{ request()->routeIs('admin.registrations.*')
                            ? 'flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700'
                            : 'sidebar-link' }}"
                    >
                        <span
                            class="{{ request()->routeIs('admin.registrations.*')
                                ? 'flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100'
                                : 'sidebar-icon' }}"
                        >
                            <i
                                data-lucide="user-plus"
                                class="h-4 w-4"
                            ></i>
                        </span>

                        Pendaftaran
                    </a>

                    <a
                        href="{{ route('admin.admissions.index') }}"
                        class="{{ request()->routeIs('admin.admissions.*')
                            ? 'flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700'
                            : 'sidebar-link' }}"
                    >
                        <span
                            class="{{ request()->routeIs('admin.admissions.*')
                                ? 'flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100'
                                : 'sidebar-icon' }}"
                        >
                            <i
                                data-lucide="circle-check-big"
                                class="h-4 w-4"
                            ></i>
                        </span>

                        Penerimaan
                    </a>

                    <a
                        href="{{ route('admin.reenrollments.index') }}"
                        class="{{ request()->routeIs('admin.reenrollments.*')
                            ? 'flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700'
                            : 'sidebar-link' }}"
                    >
                        <span
                            class="{{ request()->routeIs('admin.reenrollments.*')
                                ? 'flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100'
                                : 'sidebar-icon' }}"
                        >
                            <i
                                data-lucide="wallet-cards"
                                class="h-4 w-4"
                            ></i>
                        </span>

                        Daftar Ulang
                    </a>

                </div>
            </div>

            {{-- DATA --}}
            <div>
                <div class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                    Data
                </div>

                <div class="space-y-1">

                    <a
                        href="{{ route('admin.recaps.index') }}"
                        class="{{ request()->routeIs('admin.recaps.*')
                            ? 'flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700'
                            : 'sidebar-link' }}"
                    >
                        <span
                            class="{{ request()->routeIs('admin.recaps.*')
                                ? 'flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100'
                                : 'sidebar-icon' }}"
                        >
                            <i
                                data-lucide="chart-column-big"
                                class="h-4 w-4"
                            ></i>
                        </span>

                        Rekap
                    </a>

                    <a
                        href="{{ route('admin.analytics.index') }}"
                        class="{{ request()->routeIs('admin.analytics.*')
                            ? 'flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700'
                            : 'sidebar-link' }}"
                    >
                        <span
                            class="{{ request()->routeIs('admin.analytics.*')
                                ? 'flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100'
                                : 'sidebar-icon' }}"
                        >
                            <i
                                data-lucide="chart-no-axes-combined"
                                class="h-4 w-4"
                            ></i>
                        </span>

                        Analitik
                    </a>

                    <a
                        href="{{ route('admin.reports.index') }}"
                        class="{{ request()->routeIs('admin.reports.*')
                            ? 'flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700'
                            : 'sidebar-link' }}"
                    >
                        <span
                            class="{{ request()->routeIs('admin.reports.*')
                                ? 'flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100'
                                : 'sidebar-icon' }}"
                        >
                            <i
                                data-lucide="file-text"
                                class="h-4 w-4"
                            ></i>
                        </span>

                        Laporan
                    </a>

                </div>
            </div>

            {{-- KOMUNIKASI --}}
            <div>
                <div class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                    Komunikasi
                </div>

                <a
                    href="{{ route('admin.whatsapp-logs.index') }}"
                    class="{{ request()->routeIs('admin.whatsapp-logs.*')
                        ? 'flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700'
                        : 'sidebar-link' }}"
                >
                    <span
                        class="{{ request()->routeIs('admin.whatsapp-logs.*')
                            ? 'flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100'
                            : 'sidebar-icon' }}"
                    >
                        <i
                            data-lucide="message-circle-more"
                            class="h-4 w-4"
                        ></i>
                    </span>

                    WhatsApp
                </a>
            </div>

            {{-- SISTEM --}}
            <div>
                <div class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                    Sistem
                </div>

                <div class="space-y-1">

                    <a href="#" class="sidebar-link">
                        <span class="sidebar-icon">
                            <i
                                data-lucide="users"
                                class="h-4 w-4"
                            ></i>
                        </span>

                        Users
                    </a>

                    <a href="#" class="sidebar-link">
                        <span class="sidebar-icon">
                            <i
                                data-lucide="list-checks"
                                class="h-4 w-4"
                            ></i>
                        </span>

                        Activity Log
                    </a>

                    <a
                        href="{{ route('admin.settings.index') }}"
                        class="{{ request()->routeIs('admin.settings.*')
                            ? 'flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700'
                            : 'sidebar-link' }}"
                    >
                        <span
                            class="{{ request()->routeIs('admin.settings.*')
                                ? 'flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100'
                                : 'sidebar-icon' }}"
                        >
                            <i
                                data-lucide="settings"
                                class="h-4 w-4"
                            ></i>
                        </span>

                        Pengaturan
                    </a>

                </div>
            </div>

        </nav>
    </div>

    <div class="border-t border-slate-100 p-4">
        <div class="rounded-2xl bg-slate-50 p-4">

            <div class="text-xs font-medium text-slate-500">
                Periode Aktif
            </div>

            <div class="mt-1 flex items-center gap-2">

                @if ($activePeriod)
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>

                    <span class="font-semibold text-slate-800">
                        {{ $activePeriod->name }}
                    </span>
                @else
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>

                    <span class="font-semibold text-slate-800">
                        Belum diatur
                    </span>
                @endif

            </div>

        </div>
    </div>
</aside>