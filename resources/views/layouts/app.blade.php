<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Ardi Ponsel'))</title>

    <script>
        (function() {
            try {
                if (JSON.parse(localStorage.getItem('ap_dark'))) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body x-data="{
    darkMode: $persist(false).as('ap_dark'),
    sidebarOpen: false
}" x-init="$watch('darkMode', val => {
    document.documentElement.classList.toggle('dark', val)
});
document.documentElement.classList.toggle('dark', darkMode)"
    class="bg-neutral-50 dark:bg-neutral-950
           text-neutral-900 dark:text-neutral-100
           antialiased">

    <div class="fixed top-0 inset-x-0 z-[9999] h-0.5 pointer-event-none">
        <div id="page-loader-bar"
            class="h-full bg-primary-500 shadow-[0_0_8px] shadow-primary-500
                      w-0 opacity-0 transition-[width,opacity] duration-300 ease-out">
        </div>
    </div>
    <div class="flex min-h-screen">

        {{-- Mobile backdrop --}}
        <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-black/40 md:hidden"></div>

        {{-- Mobile sidebar --}}
        <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full" class="fixed inset-y-0 left-0 z-50 md:hidden">
            <x-layouts.sidebar />
        </div>

        {{-- Desktop sidebar --}}
        <div class="hidden md:block">
            <x-layouts.sidebar />
        </div>

        {{-- Main --}}
        <div class="flex flex-1 flex-col min-w-0">
            <x-layouts.header />

            <main class="flex-1 p-4 md:p-6 overflow-auto">

                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="mb-4 flex items-center gap-3 px-4 py-3 rounded-xl text-sm
                               bg-primary-50 dark:bg-primary-900/30
                               border border-primary-200 dark:border-primary-800
                               text-primary-800 dark:text-primary-200">
                        <x-heroicon-s-check-circle class="w-4 h-4 shrink-0 text-primary-500" />
                        {{ session('success') }}
                        <button @click="show = false" class="ml-auto">
                            <x-heroicon-s-x-mark class="w-4 h-4 text-primary-400 hover:text-primary-600" />
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="mb-4 flex items-center gap-3 px-4 py-3 rounded-xl text-sm
                               bg-red-50 dark:bg-red-900/20
                               border border-red-200 dark:border-red-800
                               text-red-700 dark:text-red-300">
                        <x-heroicon-s-exclamation-circle class="w-4 h-4 shrink-0 text-red-500" />
                        {{ session('error') }}
                        <button @click="show = false" class="ml-auto">
                            <x-heroicon-s-x-mark class="w-4 h-4 text-red-400 hover:text-red-600" />
                        </button>
                    </div>
                @endif

                @yield('content')

            </main>
        </div>
    </div>

    @stack('modals')
    @stack('scripts')
    <script>
        (function() {
            const bar = document.getElementById('page-loader-bar');
            if (!bar) return;
            let timer, width;

            function start() {
                clearInterval(timer);
                width = 0;
                bar.style.opacity = '1';
                bar.style.width = '0%';
                requestAnimationFrame(() => {
                    timer = setInterval(() => {
                        width += (90 - width) * 0.1; // merangkak ke ~90%
                        bar.style.width = width + '%';
                    }, 200);
                });
            }

            function done() {
                clearInterval(timer);
                bar.style.width = '100%';
                setTimeout(() => {
                    bar.style.opacity = '0';
                    bar.style.width = '0%';
                }, 300);
            }

            // Mulai saat klik link internal
            document.addEventListener('click', (e) => {
                const a = e.target.closest('a');
                if (!a) return;
                const href = a.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript')) return;
                if (a.target === '_blank' || a.hasAttribute('download')) return;
                if (a.target === '_blank' || a.hasAttribute('download')) return;
                if (a.origin !== location.origin) return;
                if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                start();
            });

            // Mulai saat submit form
            document.addEventListener('submit', () => start());

            // Reset kalau halaman dipulihkan dari cache (tombol back)
            window.addEventListener('pageshow', (e) => {
                if (e.persisted) done();
            });
        })();
    </script>
</body>

</html>
