@php
    $user = auth()->user();
    $shift = $user?->active_shift;

    $pageMap = [
        'dashboard' => ['title' => 'Dashboard', 'icon' => 'heroicon-o-squares-2x2'],
        'transactions' => ['title' => 'Transaksi', 'icon' => 'heroicon-o-shopping-cart'],
        'products' => ['title' => 'Produk & stok', 'icon' => 'heroicon-o-cube'],
        'services' => ['title' => 'Servis / jasa', 'icon' => 'heroicon-o-wrench-screwdriver'],
        'shifts' => ['title' => 'Kas & shift', 'icon' => 'heroicon-o-banknotes'],
        'banks' => ['title' => 'Saldo bank', 'icon' => 'heroicon-o-building-library'],
        'reports' => ['title' => 'Laporan', 'icon' => 'heroicon-o-chart-bar'],
        'employees' => ['title' => 'Karyawan', 'icon' => 'heroicon-o-users'],
        'settings' => ['title' => 'Pengaturan', 'icon' => 'heroicon-o-cog-6-tooth'],
        'activity-logs' => ['title' => 'Log aktivitas', 'icon' => 'heroicon-o-clipboard-document-list'],
        'profile' => ['title' => 'Profil saya', 'icon' => 'heroicon-o-user'],
    ];

    $routeName = request()->route()?->getName() ?? '';
    $currentPage = collect($pageMap)->first(fn($v, $k) => str_starts_with($routeName, $k)) ?? [
        'title' => 'Halaman',
        'icon' => 'heroicon-o-squares-2x2',
    ];
@endphp

<header
    class="sticky top-0 z-30 flex items-center gap-2 px-4 md:px-5 h-14 shrink-0
               bg-white dark:bg-neutral-900
               border-b border-neutral-200 dark:border-neutral-800">

    {{-- Hamburger mobile --}}
    <button @click="sidebarOpen = !sidebarOpen"
        class="md:hidden w-8 h-8 flex items-center justify-center rounded-lg
               border border-neutral-200 dark:border-neutral-700
               text-neutral-500 dark:text-neutral-400
               hover:bg-neutral-100 dark:hover:bg-neutral-800
               transition-colors duration-150"
        aria-label="Toggle sidebar">
        <x-heroicon-o-bars-3 class="w-4 h-4" />
    </button>

    {{-- Page title + breadcrumb --}}
    <div class="flex items-center gap-2 min-w-0">
        <x-dynamic-component :component="$currentPage['icon']" class="w-4 h-4 shrink-0 text-primary-600 dark:text-primary-400" />
        <h1 class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
            {{ $currentPage['title'] }}
        </h1>
        <span class="hidden sm:flex items-center gap-1 text-xs text-neutral-400 dark:text-neutral-600">
            <span>/</span>
            <span>{{ $activeBranch->name ?? '-' }}</span>
        </span>
    </div>

    <div class="flex-1"></div>

    {{-- Shift indicator --}}
    <div x-data="shiftIndicator()" x-init="load();
    setInterval(() => load(), 15000)" class="hidden sm:block">
        {{-- Shift aktif --}}
        <template x-if="shift.active">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                       bg-primary-50 dark:bg-primary-900/30
                       border border-primary-200 dark:border-primary-800
                       text-primary-800 dark:text-primary-200
                       hover:bg-primary-100 dark:hover:bg-primary-900/50
                       transition-colors duration-150">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse"></span>
                    <span x-text="'Shift ' + shift.type + ' · ' + shift.opener"></span>
                    <x-heroicon-s-chevron-down class="w-3 h-3 opacity-60" />
                </button>

                {{-- Dropdown info kas --}}
                <div x-cloak x-show="open" @click.outside="open = false"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                    class="absolute right-0 top-full mt-2 w-64 z-50 origin-top-right
                       bg-white dark:bg-neutral-800
                       border border-neutral-200 dark:border-neutral-700
                       rounded-2xl overflow-hidden">
                    <div class="px-4 py-3 border-b border-neutral-200 dark:border-neutral-700">
                        <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse"></span>
                            Shift <span x-text="shift.type"></span> — Aktif
                        </p>
                        <p class="text-[10px] text-neutral-500 mt-0.5">
                            Dibuka <span x-text="shift.opener"></span> · <span x-text="shift.opened_at"></span>
                        </p>
                    </div>

                    <div class="p-3 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-neutral-500">Modal awal</span>
                            <span class="font-medium text-neutral-900 dark:text-neutral-100"
                                x-text="'Rp ' + shift.opening_cash.toLocaleString('id-ID')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-neutral-500">Tunai masuk</span>
                            <span class="font-medium text-primary-600 dark:text-primary-400"
                                x-text="'Rp ' + shift.total_cash.toLocaleString('id-ID')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-neutral-500">Transfer masuk</span>
                            <span class="font-medium text-blue-600 dark:text-blue-400"
                                x-text="'Rp ' + shift.total_transfer.toLocaleString('id-ID')"></span>
                        </div>
                        <div class="h-px bg-neutral-100 dark:bg-neutral-700"></div>
                        <div class="flex justify-between text-xs">
                            <span class="text-neutral-500">Total kas fisik</span>
                            <span class="font-medium text-neutral-900 dark:text-neutral-100"
                                x-text="'Rp ' + (shift.opening_cash + shift.total_cash).toLocaleString('id-ID')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-neutral-500">Transaksi</span>
                            <span class="font-medium text-neutral-900 dark:text-neutral-100"
                                x-text="shift.total_transactions + ' transaksi'"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-neutral-500">Total penjualan</span>
                            <span class="font-medium text-neutral-900 dark:text-neutral-100"
                                x-text="'Rp ' + shift.total_sales.toLocaleString('id-ID')"></span>
                        </div>
                    </div>

                    <div class="px-3 pb-3">
                        <a href="{{ route('shifts.index') }}"
                            class="btn-secondary w-full justify-center !text-xs !py-1.5">
                            Lihat detail shift →
                        </a>
                    </div>
                </div>
            </div>
        </template>

        {{-- Shift belum dibuka --}}
        <template x-if="!shift.active">
            <a href="{{ route('shifts.index') }}"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                  bg-amber-50 dark:bg-amber-900/20
                  border border-amber-200 dark:border-amber-800
                  text-amber-700 dark:text-amber-300
                  hover:bg-amber-100 dark:hover:bg-amber-900/30
                  transition-colors duration-150">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                Shift belum dibuka
            </a>
        </template>
    </div>

    <div class="hidden sm:block w-px h-5 bg-neutral-200 dark:bg-neutral-700 mx-1"></div>

    {{-- Kalkulator --}}
    <div x-data="calculator()" class="relative">
        <button @click="open = !open"
            class="w-8 h-8 flex items-center justify-center rounded-lg
                   border border-neutral-200 dark:border-neutral-700
                   text-neutral-500 dark:text-neutral-400
                   hover:bg-neutral-100 dark:hover:bg-neutral-800
                   transition-colors duration-150"
            aria-label="Kalkulator cepat">
            <x-heroicon-o-calculator class="w-4 h-4" />
        </button>

        <div x-cloak x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
            class="absolute right-0 top-full mt-2 w-56 z-50 origin-top-right
                   bg-white dark:bg-neutral-800
                   border border-neutral-200 dark:border-neutral-700
                   rounded-2xl overflow-hidden">
            {{-- Display --}}
            <div class="px-4 pt-3 pb-2 bg-neutral-50 dark:bg-neutral-900/60">
                <p class="text-right text-[11px] text-neutral-400 h-4 truncate" x-text="expr || '\xa0'"></p>
                <p class="text-right text-xl font-medium text-neutral-900 dark:text-neutral-100 truncate"
                    x-text="display"></p>
            </div>

            {{-- Keypad --}}
            <div
                class="grid grid-cols-4 gap-px bg-neutral-200 dark:bg-neutral-700
                        border-t border-neutral-200 dark:border-neutral-700">
                <template x-for="btn in buttons" :key="btn.label">
                    <button @click="press(btn.label)" :class="btn.cls"
                        class="py-3 text-sm font-medium transition-colors duration-100" x-text="btn.label"></button>
                </template>
            </div>
        </div>
    </div>

    {{-- Notifikasi --}}
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open"
            class="relative w-8 h-8 flex items-center justify-center rounded-lg
                   border border-neutral-200 dark:border-neutral-700
                   text-neutral-500 dark:text-neutral-400
                   hover:bg-neutral-100 dark:hover:bg-neutral-800
                   transition-colors duration-150"
            aria-label="Notifikasi">
            <x-heroicon-o-bell class="w-4 h-4" />
            @if (($notifCount ?? 0) > 0)
                <span
                    class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-red-500
                             ring-2 ring-white dark:ring-neutral-900"></span>
            @endif
        </button>

        <div x-cloak x-show="open" @click.outside="open = false"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
            class="absolute right-0 top-full mt-2 w-80 z-50 origin-top-right
                   bg-white dark:bg-neutral-800
                   border border-neutral-200 dark:border-neutral-700
                   rounded-2xl overflow-hidden">
            <div
                class="flex items-center justify-between px-4 py-3
                        border-b border-neutral-200 dark:border-neutral-700">
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    Notifikasi
                </p>
                @if (($notifCount ?? 0) > 0)
                    <span
                        class="text-[10px] px-2 py-0.5 rounded-full font-medium
                                 bg-red-100 dark:bg-red-900/30
                                 text-red-700 dark:text-red-300">
                        {{ $notifCount }} baru
                    </span>
                @endif
            </div>

            <div
                class="divide-y divide-neutral-100 dark:divide-neutral-700/60
                        max-h-72 overflow-y-auto">
                @forelse ($notifications ?? [] as $notif)
                    <div
                        class="flex gap-3 px-4 py-3 cursor-pointer
                                hover:bg-neutral-50 dark:hover:bg-neutral-700/40
                                transition-colors duration-150">
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center
                                    shrink-0 mt-0.5 {{ $notif['bg'] }}">
                            <x-dynamic-component :component="$notif['icon']" class="w-4 h-4 {{ $notif['icon_color'] }}" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                                {{ $notif['title'] }}
                            </p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5 truncate">
                                {{ $notif['body'] }}
                            </p>
                            <p class="text-[10px] text-neutral-400 mt-1">
                                {{ $notif['time'] }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center">
                        <x-heroicon-o-bell-slash class="w-6 h-6 text-neutral-300 dark:text-neutral-600 mx-auto mb-2" />
                        <p class="text-xs text-neutral-400">Tidak ada notifikasi baru</p>
                    </div>
                @endforelse
            </div>

            <div class="px-4 py-2.5 border-t border-neutral-200 dark:border-neutral-700">
                <a href="#" class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:underline">
                    Lihat semua notifikasi →
                </a>
            </div>
        </div>
    </div>

    <div class="w-px h-5 bg-neutral-200 dark:bg-neutral-700 mx-1"></div>

    {{-- Dark mode toggle --}}
    <div
        class="flex items-center gap-0.5 p-1
                bg-neutral-100 dark:bg-neutral-800
                border border-neutral-200 dark:border-neutral-700
                rounded-lg">
        <button @click="darkMode = false"
            :class="!darkMode
                ?
                'bg-white text-primary-700 border border-neutral-200 shadow-sm' :
                'text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300'"
            class="flex items-center gap-1.5 px-2.5 py-1 rounded-md
                   text-xs font-medium transition-all duration-150"
            aria-label="Light mode">
            <x-heroicon-o-sun class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Light</span>
        </button>
        <button @click="darkMode = true"
            :class="darkMode
                ?
                'bg-neutral-700 text-primary-300 border border-neutral-600 shadow-sm' :
                'text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300'"
            class="flex items-center gap-1.5 px-2.5 py-1 rounded-md
                   text-xs font-medium transition-all duration-150"
            aria-label="Dark mode">
            <x-heroicon-o-moon class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Dark</span>
        </button>
    </div>

</header>

@once
    @push('scripts')
        <script>
            function calculator() {
                return {
                    open: false,
                    display: '0',
                    expr: '',
                    current: '0',
                    op: null,
                    prev: null,
                    fresh: false,

                    buttons: [{
                            label: 'AC',
                            cls: 'bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200 hover:bg-neutral-200 dark:hover:bg-neutral-600'
                        },
                        {
                            label: '±',
                            cls: 'bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200 hover:bg-neutral-200 dark:hover:bg-neutral-600'
                        },
                        {
                            label: '%',
                            cls: 'bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200 hover:bg-neutral-200 dark:hover:bg-neutral-600'
                        },
                        {
                            label: '÷',
                            cls: 'bg-primary-500 hover:bg-primary-600 text-white'
                        },
                        {
                            label: '7',
                            cls: 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-700'
                        },
                        {
                            label: '8',
                            cls: 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-700'
                        },
                        {
                            label: '9',
                            cls: 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-700'
                        },
                        {
                            label: '×',
                            cls: 'bg-primary-500 hover:bg-primary-600 text-white'
                        },
                        {
                            label: '4',
                            cls: 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-700'
                        },
                        {
                            label: '5',
                            cls: 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-700'
                        },
                        {
                            label: '6',
                            cls: 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-700'
                        },
                        {
                            label: '−',
                            cls: 'bg-primary-500 hover:bg-primary-600 text-white'
                        },
                        {
                            label: '1',
                            cls: 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-700'
                        },
                        {
                            label: '2',
                            cls: 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-700'
                        },
                        {
                            label: '3',
                            cls: 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-700'
                        },
                        {
                            label: '+',
                            cls: 'bg-primary-500 hover:bg-primary-600 text-white'
                        },
                        {
                            label: '0',
                            cls: 'col-span-2 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-700 text-left pl-5'
                        },
                        {
                            label: '.',
                            cls: 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-700'
                        },
                        {
                            label: '=',
                            cls: 'bg-primary-600 hover:bg-primary-700 text-white'
                        },
                    ],

                    press(v) {
                        if (v === 'AC') {
                            this.display = '0';
                            this.expr = '';
                            this.current = '0';
                            this.op = null;
                            this.prev = null;
                            this.fresh = false;
                            return;
                        }
                        if (v === '±') {
                            this.current = String(-parseFloat(this.current));
                            this.display = this.current;
                            return;
                        }
                        if (v === '%') {
                            this.current = String(parseFloat(this.current) / 100);
                            this.display = this.current;
                            return;
                        }
                        if (['÷', '×', '−', '+'].includes(v)) {
                            this.prev = parseFloat(this.current);
                            this.op = v;
                            this.expr = this.current + ' ' + v;
                            this.fresh = true;
                            return;
                        }
                        if (v === '=') {
                            if (!this.op || this.prev === null) return;
                            const b = parseFloat(this.current);
                            const map = {
                                '+': (a, b) => a + b,
                                '−': (a, b) => a - b,
                                '×': (a, b) => a * b,
                                '÷': (a, b) => b !== 0 ? a / b : 0,
                            };
                            const res = map[this.op](this.prev, b);
                            this.expr = this.expr + ' ' + this.current + ' =';
                            this.current = String(Math.round(res * 1e10) / 1e10);
                            this.display = this.current;
                            this.op = null;
                            this.prev = null;
                            this.fresh = false;
                            return;
                        }
                        if (v === '.') {
                            if (this.fresh) {
                                this.current = '0.';
                                this.fresh = false;
                            } else if (!this.current.includes('.')) this.current += '.';
                            this.display = this.current;
                            return;
                        }
                        if (this.fresh || this.current === '0') {
                            this.current = v;
                            this.fresh = false;
                        } else {
                            this.current += v;
                        }
                        this.display = this.current;
                    }
                }
            }

            function shiftIndicator() {
                return {
                    shift: {
                        active: false,
                        type: '',
                        opener: '',
                        opening_cash: 0,
                        total_cash: 0,
                        total_transfer: 0,
                        total_sales: 0,
                        total_transactions: 0,
                        opened_at: '',
                    },

                    async load() {
                        try {
                            const res = await fetch('{{ route('shifts.status') }}', {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            })
                            const data = await res.json()
                            this.shift = data
                        } catch (e) {
                            // silent fail
                        }
                    }
                }
            }
        </script>
    @endpush
@endonce
