<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: $persist(false).as('ap_dark') }" x-init="document.documentElement.classList.toggle('dark', darkMode);
$watch('darkMode', val => document.documentElement.classList.toggle('dark', val))"
    :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — {{ config('app.name', 'Ardi Ponsel') }}</title>

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
</head>

<body class="min-h-screen bg-neutral-50 dark:bg-neutral-950 antialiased relative">

    {{-- Toggle pojok kanan atas --}}
    <div
        class="absolute top-4 right-4 z-10
                flex items-center gap-0.5 p-1
                bg-neutral-100 dark:bg-neutral-800
                border border-neutral-200 dark:border-neutral-700
                rounded-lg">
        <button @click="darkMode = false"
            :class="!darkMode
                ?
                'bg-white text-primary-700 border border-neutral-200' :
                'text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300'"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md
                   text-xs font-medium transition-all duration-150">
            <x-heroicon-o-sun class="w-3.5 h-3.5" />
            Light
        </button>
        <button @click="darkMode = true"
            :class="darkMode
                ?
                'bg-neutral-700 text-primary-300 border border-neutral-600' :
                'text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300'"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md
                   text-xs font-medium transition-all duration-150">
            <x-heroicon-o-moon class="w-3.5 h-3.5" />
            Dark
        </button>
    </div>

    <div class="min-h-screen flex">

        {{-- Kiri — branding --}}
        <div class="hidden lg:flex lg:w-1/2 bg-primary-600 flex-col justify-between p-12">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                    <x-heroicon-s-device-phone-mobile class="w-5 h-5 text-white" />
                </div>
                <div>
                    <p class="text-white font-medium text-sm leading-tight">Ardi Ponsel</p>
                    <p class="text-primary-200 text-xs mt-0.5">Point of Sale</p>
                </div>
            </div>

            <div>
                <h1 class="text-white text-3xl font-medium leading-snug mb-4">
                    Kelola toko ponsel<br>lebih mudah & cepat
                </h1>
                <p class="text-primary-100 text-sm leading-relaxed">
                    Sistem POS lengkap untuk transaksi, stok, shift kasir,<br>
                    laporan, dan multi cabang dalam satu platform.
                </p>

                <div class="mt-10 grid grid-cols-2 gap-4">
                    <div class="bg-white/10 rounded-xl p-4">
                        <x-heroicon-o-shopping-cart class="w-5 h-5 text-primary-200 mb-2" />
                        <p class="text-white text-xs font-medium">Transaksi cepat</p>
                        <p class="text-primary-200 text-xs mt-0.5">Tunai, transfer, campur</p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4">
                        <x-heroicon-o-cube class="w-5 h-5 text-primary-200 mb-2" />
                        <p class="text-white text-xs font-medium">Manajemen stok</p>
                        <p class="text-primary-200 text-xs mt-0.5">Alert stok menipis</p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4">
                        <x-heroicon-o-chart-bar class="w-5 h-5 text-primary-200 mb-2" />
                        <p class="text-white text-xs font-medium">Laporan otomatis</p>
                        <p class="text-primary-200 text-xs mt-0.5">Per shift & cabang</p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4">
                        <x-heroicon-o-building-office-2 class="w-5 h-5 text-primary-200 mb-2" />
                        <p class="text-white text-xs font-medium">Multi cabang</p>
                        <p class="text-primary-200 text-xs mt-0.5">Data terpisah per cabang</p>
                    </div>
                </div>
            </div>

            <p class="text-primary-300 text-xs">
                © {{ date('Y') }} Ardi Ponsel. All rights reserved.
            </p>
        </div>

        {{-- Kanan — form --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6">
            <div class="w-full max-w-sm">

                {{-- Logo mobile --}}
                <div class="flex items-center gap-3 mb-8 lg:hidden">
                    <div class="w-9 h-9 rounded-xl bg-primary-600 flex items-center justify-center">
                        <x-heroicon-s-device-phone-mobile class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 leading-tight">
                            Ardi Ponsel
                        </p>
                        <p class="text-xs text-neutral-400 mt-0.5">Point of Sale</p>
                    </div>
                </div>

                <h2 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-1">
                    Selamat datang
                </h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-7">
                    Masuk menggunakan User ID atau email kamu
                </p>

                {{-- Session error --}}
                @if (session('error'))
                    <div
                        class="mb-4 flex items-start gap-2.5 px-3 py-3 rounded-xl text-xs
                                bg-red-50 dark:bg-red-900/20
                                border border-red-200 dark:border-red-800
                                text-red-700 dark:text-red-300">
                        <x-heroicon-s-exclamation-circle class="w-4 h-4 shrink-0 mt-0.5 text-red-500" />
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Validation error --}}
                @if ($errors->any())
                    <div
                        class="mb-4 flex items-start gap-2.5 px-3 py-3 rounded-xl text-xs
                                bg-red-50 dark:bg-red-900/20
                                border border-red-200 dark:border-red-800
                                text-red-700 dark:text-red-300">
                        <x-heroicon-s-exclamation-circle class="w-4 h-4 shrink-0 mt-0.5 text-red-500" />
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login-proses') }}" class="space-y-4">
                    @csrf

                    {{-- User ID / Email --}}
                    <div>
                        <label for="user_id"
                            class="block text-xs font-medium
                                      text-neutral-600 dark:text-neutral-400 mb-1.5">
                            User ID / Email
                        </label>
                        <input id="user_id" type="text" name="user_id" value="{{ old('user_id') }}" required
                            autofocus autocomplete="username" placeholder="kasir001 atau nama@email.com"
                            class="w-full px-3 py-2.5 text-sm rounded-xl
                                   border border-neutral-200 dark:border-neutral-700
                                   bg-white dark:bg-neutral-800
                                   text-neutral-900 dark:text-neutral-100
                                   placeholder:text-neutral-400 dark:placeholder:text-neutral-500
                                   focus:outline-none focus:ring-2 focus:ring-primary-500/30
                                   focus:border-primary-500 transition-all duration-150
                                   {{ $errors->has('user_id') ? 'border-red-400 dark:border-red-600' : '' }}">
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password"
                            class="block text-xs font-medium
                                      text-neutral-600 dark:text-neutral-400 mb-1.5">
                            Password
                        </label>
                        <div x-data="{ show: false }" class="relative">
                            <input id="password" :type="show ? 'text' : 'password'" name="password" required
                                autocomplete="current-password" placeholder="••••••••"
                                class="w-full px-3 py-2.5 pr-10 text-sm rounded-xl
                                       border border-neutral-200 dark:border-neutral-700
                                       bg-white dark:bg-neutral-800
                                       text-neutral-900 dark:text-neutral-100
                                       placeholder:text-neutral-400 dark:placeholder:text-neutral-500
                                       focus:outline-none focus:ring-2 focus:ring-primary-500/30
                                       focus:border-primary-500 transition-all duration-150
                                       {{ $errors->has('password') ? 'border-red-400 dark:border-red-600' : '' }}">
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2
                                       text-neutral-400 hover:text-neutral-600
                                       dark:hover:text-neutral-300 transition-colors duration-150">
                                <x-heroicon-o-eye x-show="!show" class="w-4 h-4" />
                                <x-heroicon-o-eye-slash x-show="show" x-cloak class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center gap-2">
                        <input id="remember" type="checkbox" name="remember"
                            class="w-4 h-4 rounded border-neutral-300 dark:border-neutral-600
                                   accent-primary-600">
                        <label for="remember" class="text-xs text-neutral-600 dark:text-neutral-400 cursor-pointer">
                            Ingat saya
                        </label>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5
                               text-sm font-medium rounded-xl
                               bg-primary-600 hover:bg-primary-700 active:bg-primary-800
                               text-white transition-colors duration-150">
                        <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4" />
                        Masuk
                    </button>

                </form>

            </div>
        </div>

    </div>

</body>

</html>
