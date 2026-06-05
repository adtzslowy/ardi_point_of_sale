@php
    $user = auth()->user();
    $branch = $user?->branch;
    $role = $user?->getRoleNames()->first() ?? 'kasir';

    $groups = [
        'Utama' => [
            [
                'label' => 'Dashboard',
                'icon' => 'heroicon-o-squares-2x2',
                'active' => request()->routeIs('dashboard'),
                'route' => 'dashboard',
            ],
            [
                'label' => 'Transaksi',
                'icon' => 'heroicon-o-shopping-cart',
                'active' => request()->routeIs('transactions.*'),
                'badge' => 3,
                'badge_color' => 'primary',
                'route' => 'transactions.index'
            ],
            [
                'label' => 'Produk & Stok',
                'icon' => 'heroicon-o-cube',
                'active' => request()->routeIs('products.*'),
                'badge' => '!',
                'badge_color' => 'warning',
                'route' => 'products.index'
            ],
            [
                'label' => 'Jasa / Servis',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'active' => request()->routeIs('services.*'),
                'route' => 'services.index',
            ],
        ],
        'Keuangan' => [
            ['label' => 'Kas & shift', 'icon' => 'heroicon-o-banknotes', 'active' => request()->routeIs('shifts.*'), 'route' => 'shifts.index'],
            [
                'label' => 'Saldo bank',
                'icon' => 'heroicon-o-building-library',
                'active' => request()->routeIs('banks.*'),
            ],
            ['label' => 'Laporan', 'icon' => 'heroicon-o-chart-bar', 'active' => request()->routeIs('reports.*')],
        ],
        'Pengaturan' => [
            ['label' => 'Karyawan', 'icon' => 'heroicon-o-users', 'active' => request()->routeIs('user-manage.*'), 'route' => 'user-manage.index'],
            ['label' => 'Pengaturan', 'icon' => 'heroicon-o-cog-6-tooth', 'active' => request()->routeIs('settings.*')],
            [
                'label' => 'Log aktivitas',
                'icon' => 'heroicon-o-clipboard-document-list',
                'active' => request()->routeIs('activity-logs.*'),
            ],
        ],
    ];
@endphp

<aside
    class="flex flex-col w-60 h-screen sticky top-0 shrink-0
              bg-white dark:bg-neutral-900
              border-r border-neutral-200 dark:border-neutral-800"
    aria-label="Navigasi utama">

    {{-- Logo --}}
    <div
        class="flex items-center gap-3 px-4 py-[18px] shrink-0
                border-b border-neutral-200 dark:border-neutral-800">
        <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center shrink-0">
            <x-heroicon-s-device-phone-mobile class="w-[18px] h-[18px] text-white" />
        </div>
        <div class="min-w-0">
            <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 leading-tight truncate">
                Ardi Ponsel
            </p>
            <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-0.5">
                Point of Sale
            </p>
        </div>
    </div>

    @php
        $allBranches = auth()->user()->hasRole('owner')
            ? \App\Models\Branche::where('is_active', true)->get()
            : collect();
    @endphp

    <div class="px-3 pt-3 pb-1 shrink-0">
        @if (auth()->user()->hasRole('owner') && $allBranches->count() > 1)
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-left
                       bg-neutral-50 dark:bg-neutral-800
                       border border-neutral-200 dark:border-neutral-700
                       hover:border-primary-400 dark:hover:border-primary-600
                       transition-colors duration-150 group">
                    <span class="w-2 h-2 rounded-full bg-primary-500 shrink-0 animate-pulse"></span>
                    <span class="text-xs font-medium text-neutral-800 dark:text-neutral-200 flex-1 truncate">
                        {{ $activeBranch->name }}
                    </span>
                    <x-heroicon-s-chevron-up-down
                        class="w-3.5 h-3.5 shrink-0 text-neutral-400
                           group-hover:text-primary-500 transition-colors duration-150" />
                </button>

                <div x-show="open" x-cloak @click.outside="open = false"
                    x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    class="absolute left-0 right-0 top-full mt-1 z-50
                       bg-white dark:bg-neutral-800
                       border border-neutral-200 dark:border-neutral-700
                       rounded-xl overflow-hidden origin-top">
                    <p
                        class="px-3 py-2 text-[10px] font-medium uppercase tracking-widest
                          text-neutral-400 dark:text-neutral-600">
                        Pilih cabang
                    </p>

                    @foreach ($allBranches as $branch)
                        <form method="POST" action="{{ route('branch.switch') }}">
                            @csrf
                            <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                            <button type="submit"
                                class="w-full flex items-center gap-2.5 px-3 py-2.5 text-sm
                                   text-left transition-colors duration-150
                                   {{ $activeBranch->id === $branch->id
                                       ? 'bg-primary-50 dark:bg-primary-900/40 text-primary-800 dark:text-primary-200 font-medium'
                                       : 'text-neutral-700 dark:text-neutral-200 hover:bg-neutral-50 dark:hover:bg-neutral-700' }}">
                                <span
                                    class="w-1.5 h-1.5 rounded-full shrink-0
                                         {{ $activeBranch->id === $branch->id ? 'bg-primary-500' : 'bg-neutral-300 dark:bg-neutral-600' }}">
                                </span>
                                {{ $branch->name }}
                                @if ($activeBranch->id === $branch->id)
                                    <x-heroicon-s-check class="w-3.5 h-3.5 ml-auto text-primary-500" />
                                @endif
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Non-owner, tampil biasa tanpa dropdown --}}
            <div
                class="flex items-center gap-2 px-3 py-2 rounded-lg
                    bg-neutral-50 dark:bg-neutral-800
                    border border-neutral-200 dark:border-neutral-700">
                <span class="w-2 h-2 rounded-full bg-primary-500 shrink-0 animate-pulse"></span>
                <span class="text-xs font-medium text-neutral-800 dark:text-neutral-200 truncate">
                    {{ $activeBranch->name }}
                </span>
            </div>
        @endif
    </div>
    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-2 py-1.5" aria-label="Menu utama">
        @foreach ($groups as $groupLabel => $items)
            <p
                class="px-3 pt-3 pb-1 text-[10px] font-medium uppercase tracking-widest
                      text-neutral-400 dark:text-neutral-600 select-none">
                {{ $groupLabel }}
            </p>

            @foreach ($items as $item)
                <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}"
                    aria-current="{{ $item['active'] ? 'page' : 'false' }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm
                          transition-colors duration-150 cursor-pointer select-none
                          {{ $item['active']
                              ? 'bg-primary-50 dark:bg-primary-900/40 text-primary-800 dark:text-primary-200 font-medium'
                              : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800 hover:text-neutral-900 dark:hover:text-neutral-100' }}">

                    <x-dynamic-component :component="$item['icon']"
                        class="w-[18px] h-[18px] shrink-0
                               {{ $item['active'] ? 'text-primary-600 dark:text-primary-400' : '' }}" />

                    <span class="flex-1 truncate">{{ $item['label'] }}</span>

                    @if (!empty($item['badge']))
                        <span
                            class="text-[10px] px-1.5 py-0.5 rounded-full font-medium
                                     {{ ($item['badge_color'] ?? '') === 'primary'
                                         ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300'
                                         : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' }}">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    {{-- User profile --}}
    <div class="px-3 py-3 shrink-0 border-t border-neutral-200 dark:border-neutral-800">
        <div x-data="{ open: false }" class="relative">

            <button @click="open = !open" :aria-expanded="open" aria-haspopup="menu"
                class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg
                           hover:bg-neutral-100 dark:hover:bg-neutral-800
                           transition-colors duration-150 group">

                <div
                    class="w-7 h-7 rounded-full shrink-0
                            bg-primary-100 dark:bg-primary-900/50
                            flex items-center justify-center">
                    <span class="text-[11px] font-medium text-primary-700 dark:text-primary-300">
                        {{ strtoupper(substr($user?->name ?? 'U', 0, 2)) }}
                    </span>
                </div>

                <div class="flex-1 text-left min-w-0">
                    <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100 leading-tight truncate">
                        {{ $user?->name ?? 'Pengguna' }}
                    </p>
                    <p class="text-[10px] text-neutral-500 capitalize mt-0.5 truncate">
                        {{ $user?->hasRole('owner') ? 'Owner Konter' : ($role . ' · ' . ($branch?->name ?? '-')) }}
                    </p>
                </div>

                <x-heroicon-s-ellipsis-vertical
                    class="w-4 h-4 shrink-0 text-neutral-400
                                                        group-hover:text-neutral-600 dark:group-hover:text-neutral-300
                                                        transition-colors duration-150" />
            </button>

            {{-- Dropdown --}}
            <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95" role="menu"
                class="absolute bottom-full left-0 right-0 mb-1
                        bg-white dark:bg-neutral-800
                        border border-neutral-200 dark:border-neutral-700
                        rounded-xl overflow-hidden z-50 origin-bottom">

                <a href="#" role="menuitem"
                    class="flex items-center gap-2.5 px-3 py-2.5 text-sm
                          text-neutral-700 dark:text-neutral-200
                          hover:bg-neutral-50 dark:hover:bg-neutral-700
                          transition-colors duration-150">
                    <x-heroicon-o-user class="w-4 h-4 text-neutral-400 shrink-0" />
                    Profil saya
                </a>

                <a href="#" role="menuitem"
                    class="flex items-center gap-2.5 px-3 py-2.5 text-sm
                          text-neutral-700 dark:text-neutral-200
                          hover:bg-neutral-50 dark:hover:bg-neutral-700
                          transition-colors duration-150">
                    <x-heroicon-o-cog-6-tooth class="w-4 h-4 text-neutral-400 shrink-0" />
                    Pengaturan
                </a>

                <div class="h-px bg-neutral-100 dark:bg-neutral-700 mx-2 my-0.5"></div>

                <a href="{{ route('logout') }}" role="menuitem"
    class="flex items-center gap-2.5 px-3 py-2.5 text-sm
           text-red-600 dark:text-red-400
           hover:bg-red-50 dark:hover:bg-red-900/20
           transition-colors duration-150"
>
    <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 shrink-0" />
    Keluar</a>

            </div>
        </div>
    </div>

</aside>
