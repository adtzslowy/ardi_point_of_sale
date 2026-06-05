@extends('layouts.app')
@section('title', 'Jasa / Servis')

@section('content')

<div
    x-data="{
        open: false,
        svc: { id: '', name: '', kind: 'servis', price: 0, default_fee: 0 },
        nominal: 0,
        fee: 0,
        qty: 1,
        payment_method: 'cash',
        hasShift: {{ $activeShift ? 'true' : 'false' }},
        sellTemplate: '{{ route('services.sell', '__ID__') }}',
        formatNum(val) {
            if (!val || val == 0) return ''
            return new Intl.NumberFormat('id-ID').format(val)
        },
        openSell(svc) {
            this.svc = svc
            this.nominal = 0
            this.fee = svc.default_fee
            this.qty = 1
            this.payment_method = 'cash'
            this.open = true
        },
        get action() {
            return this.sellTemplate.replace('__ID__', this.svc.id)
        },
        get total() {
            return this.svc.kind === 'keuangan' ? this.fee : (this.svc.price * this.qty)
        }
    }"
    @keydown.escape.window="open = false"
>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Jasa / Servis</h2>
        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
            Kelola jasa servis & jasa keuangan (tarik tunai, transfer, token, dll)
        </p>
    </div>
    <a href="{{ route('services.create') }}" class="btn-primary">
        <x-heroicon-o-plus class="w-4 h-4" />
        Tambah jasa
    </a>
</div>

@unless ($activeShift)
    <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-xl text-sm
                bg-amber-50 dark:bg-amber-900/20
                border border-amber-200 dark:border-amber-800
                text-amber-700 dark:text-amber-300">
        <x-heroicon-s-exclamation-triangle class="w-4 h-4 shrink-0" />
        Shift belum dibuka. Buka shift dulu di
        <a href="{{ route('shifts.index') }}" class="font-medium underline">Kas &amp; shift</a>
        untuk bisa mencatat jasa.
    </div>
@endunless

{{-- Stats --}}
<div class="grid grid-cols-2 gap-3 mb-5">
    <div class="stat-card">
        <div class="flex items-center justify-between mb-1">
            <span class="stat-label">Jasa servis</span>
            <div class="w-6 h-6 rounded-lg bg-primary-50 dark:bg-primary-900/30
                        flex items-center justify-center">
                <x-heroicon-o-wrench-screwdriver class="w-3 h-3 text-primary-600 dark:text-primary-400" />
            </div>
        </div>
        <p class="stat-value">{{ $servisCount }}</p>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between mb-1">
            <span class="stat-label">Jasa keuangan</span>
            <div class="w-6 h-6 rounded-lg bg-primary-50 dark:bg-primary-900/30
                        flex items-center justify-center">
                <x-heroicon-o-banknotes class="w-3 h-3 text-primary-600 dark:text-primary-400" />
            </div>
        </div>
        <p class="stat-value">{{ $financeCount }}</p>
    </div>
</div>

{{-- Filter & tabel --}}
<div class="card p-0 overflow-hidden">

    <form method="GET" action="{{ route('services.index') }}"
          class="flex flex-wrap items-center gap-2 px-4 py-3
                 border-b border-neutral-200 dark:border-neutral-800">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Cari nama jasa..." class="input !w-44 !py-1.5 text-xs">
        <select name="kind" class="select !w-40 !py-1.5 text-xs">
            <option value="">Semua jenis</option>
            <option value="servis"   {{ request('kind') === 'servis'   ? 'selected' : '' }}>Jasa servis</option>
            <option value="keuangan" {{ request('kind') === 'keuangan' ? 'selected' : '' }}>Jasa keuangan</option>
        </select>
        <select name="category" class="select !w-40 !py-1.5 text-xs">
            <option value="">Semua kategori</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') === $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        <select name="status" class="select !w-32 !py-1.5 text-xs">
            <option value="active"   {{ request('status', 'active') === 'active'   ? 'selected' : '' }}>Aktif</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
        </select>
        <button type="submit" class="btn-primary !py-1.5 !text-xs">Filter</button>
        @if (request()->hasAny(['search', 'kind', 'category', 'status']))
            <a href="{{ route('services.index') }}" class="btn-secondary !py-1.5 !text-xs">Reset</a>
        @endif
    </form>

    <div class="overflow-x-auto">
        <table class="table-base">
            <thead>
                <tr>
                    <th>Jasa</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th class="text-right">Harga / Fee</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($services as $service)
                    <tr>
                        <td>
                            <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                                {{ $service->name }}
                            </p>
                        </td>
                        <td>
                            @if ($service->kind === 'keuangan')
                                <span class="badge-primary">Keuangan</span>
                            @else
                                <span class="badge-neutral">Servis</span>
                            @endif
                        </td>
                        <td class="text-xs text-neutral-500">
                            {{ $service->category?->name ?? '-' }}
                        </td>
                        <td class="text-right text-xs">
                            @if ($service->kind === 'keuangan')
                                <span class="text-neutral-500">Fee </span>
                                Rp {{ number_format($service->default_fee, 0, ',', '.') }}
                            @else
                                Rp {{ number_format($service->price, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($service->is_active)
                                <span class="badge-primary">Aktif</span>
                            @else
                                <span class="badge-neutral">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($service->is_active && $activeShift)
                                    <button type="button"
                                            @click='openSell(@json([
                                                "id" => $service->id,
                                                "name" => $service->name,
                                                "kind" => $service->kind,
                                                "price" => (int) $service->price,
                                                "default_fee" => (int) $service->default_fee,
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT))'
                                            class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                        Catat
                                    </button>
                                @endif
                                <a href="{{ route('services.edit', $service) }}"
                                   class="text-xs text-neutral-500 hover:text-neutral-700
                                          dark:text-neutral-400 dark:hover:text-neutral-200">
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-xs text-neutral-400">
                            Belum ada jasa
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($services->hasPages())
        <div class="px-4 py-3 border-t border-neutral-200 dark:border-neutral-800">
            {{ $services->links() }}
        </div>
    @endif
</div>

{{-- Modal catat jasa --}}
<div x-cloak x-show="open"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
    <form method="POST" :action="action" @click.outside="open = false"
          class="w-full max-w-sm bg-white dark:bg-neutral-900
                 border border-neutral-200 dark:border-neutral-800
                 rounded-2xl overflow-hidden">
        @csrf

        <div class="px-5 py-4 space-y-3">
            <div>
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    Catat jasa
                </p>
                <p class="text-xs text-neutral-500" x-text="svc.name"></p>
            </div>

            {{-- Jasa keuangan: nominal + fee --}}
            <template x-if="svc.kind === 'keuangan'">
                <div class="space-y-3">
                    <div>
                        <label class="label">Nominal (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                            <input type="text" :value="formatNum(nominal)"
                                   @input="nominal = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                   class="input pl-9" placeholder="mis: 100.000">
                            <input type="hidden" name="nominal" :value="nominal">
                        </div>
                    </div>
                    <div>
                        <label class="label">Biaya admin / fee (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                            <input type="text" :value="formatNum(fee)"
                                   @input="fee = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                   class="input pl-9">
                            <input type="hidden" name="fee" :value="fee">
                        </div>
                        <p class="text-[11px] text-neutral-400 mt-1">Fee inilah yang jadi profit konter.</p>
                    </div>
                </div>
            </template>

            {{-- Jasa servis: qty --}}
            <template x-if="svc.kind === 'servis'">
                <div>
                    <label class="label">Jumlah</label>
                    <input type="number" name="qty" x-model.number="qty" min="1" class="input">
                </div>
            </template>

            <div>
                <label class="label">Metode bayar</label>
                <select name="payment_method" x-model="payment_method" class="select">
                    <option value="cash">Tunai</option>
                    <option value="transfer">Transfer</option>
                </select>
            </div>

            <div>
                <label class="label">Catatan</label>
                <textarea name="note" rows="2" class="input resize-none"
                          placeholder="Opsional..."></textarea>
            </div>

            <div class="flex items-center justify-between px-3 py-2.5 rounded-lg
                        bg-primary-50 dark:bg-primary-900/20
                        border border-primary-200 dark:border-primary-800">
                <span class="text-xs text-primary-700 dark:text-primary-300">Total dibayar</span>
                <span class="text-sm font-semibold text-primary-700 dark:text-primary-300"
                      x-text="'Rp ' + total.toLocaleString('id-ID')"></span>
            </div>
        </div>

        <div class="flex gap-2 px-5 py-4 border-t border-neutral-200 dark:border-neutral-800">
            <button type="button" @click="open = false"
                    class="btn-secondary flex-1 justify-center !text-xs">
                Batal
            </button>
            <button type="submit" class="btn-primary flex-1 justify-center !text-xs">
                <x-heroicon-o-check class="w-3.5 h-3.5" />
                Catat
            </button>
        </div>
    </form>
</div>

</div>

@endsection
