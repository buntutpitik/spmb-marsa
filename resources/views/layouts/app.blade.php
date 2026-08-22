<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name', 'PPDB MARSA') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-full bg-slate-50 text-slate-900 antialiased">
    <div
        x-data="{ sidebarOpen: false }"
        class="min-h-screen"
    >
        {{-- Mobile overlay --}}
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
            @click="sidebarOpen = false"
            style="display: none;"
        ></div>

        @include('partials.sidebar')

        <div class="lg:pl-72">
            @include('partials.topbar')

            <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>