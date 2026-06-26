@extends('layouts.app')
@section('title', 'Layanan & Jasa')

@section('content')

<div
    x-data="{
        open: false,
        svc: { id: '', name: '', kind: 'servis', price: 0, default_fee: 0, cash_direction: 'none', fee_tiers: [], rita_balance: 0, product_stock: 0, product_name: '' },
        nominal: 0,
        fee: 0,
        sell_price: 0,
        cost: 0,
        qty: 1,
        payment_method: 'cash',
        bank_account_id: '',
        bankAccounts: {{ Js::from($bankAccounts) }},
        hasShift: {{ $activeShift ? 'true' : 'false' }},
        sellTemplate: '{{ route('services.sell', '__ID__') }}',
        formatNum(val) {
            if (!val || val == 0) return ''
            return new Intl.NumberFormat('id-ID').format(val)
        },
        openSell(svc) {
            this.svc = { cash_direction: 'none', fee_tiers: [], ...svc }
            this.nominal = 0
            this.fee = svc.default_fee || 0
            this.sell_price = 0
            this.cost = 0
            this.qty = 1
            this.payment_method = 'cash'
            this.bank_account_id = ''
            this.open = true
        },
        computeFee() {
            const tiers = (this.svc.fee_tiers || [])
                .map(t => ({ max: (t.max === '' || t.max === null || t.max === undefined) ? null : parseInt(t.max), fee: parseInt(t.fee) || 0 }))
                .sort((a, b) => (a.max ?? Infinity) - (b.max ?? Infinity))
            if (tiers.length === 0) { this.fee = this.svc.default_fee || 0; return }
            for (const t of tiers) {
                if (t.max === null || this.nominal <= t.max) { this.fee = t.fee; return }
            }
            this.fee = tiers[tiers.length - 1].fee
        },
        get movesCash() {
            return this.svc.kind === 'keuangan' && (this.svc.cash_direction === 'tarik' || this.svc.cash_direction === 'setor')
        },
        get cashEffect() {
            if (!this.movesCash) return 0
            return this.svc.cash_direction === 'tarik' ? (this.nominal + this.fee) : (this.fee - this.nominal)
        },
        get canSell() {
            if (this.svc.kind === 'keuangan') {
                if (!this.nominal || this.nominal < 1) return false
                if (this.movesCash && !this.bank_account_id) return false
            }
            if (this.svc.kind === 'eceran') {
                if (!this.sell_price || this.sell_price < 1) return false
            }
            if (this.svc.kind === 'rita') {
                if (!this.sell_price || this.sell_price < 1) return false
                if (!this.qty || this.qty < 1) return false
                if (this.qty > (this.svc.product_stock || 0)) return false
                if (this.cost > (this.svc.rita_balance || 0)) return false
            }
            if (this.payment_method === 'transfer' && !this.movesCash && !this.bank_account_id) return false
            return true
        },
        get action() {
            return this.sellTemplate.replace('__ID__', this.svc.id)
        },
        get eceranProfit() {
            return this.sell_price - this.cost
        },
        get total() {
            if (this.svc.kind === 'keuangan') return this.movesCash ? this.fee : (this.nominal + this.fee)
            if (this.svc.kind === 'eceran') return this.sell_price * this.qty
            if (this.svc.kind === 'rita') return this.sell_price
            return this.svc.price * this.qty
        }
    }"
    @keydown.escape.window="open = false"
>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Layanan & Jasa</h2>
        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
            Layanan digital (PPOB, pulsa, transfer, e-wallet) & jasa servis
        </p>
    </div>
    <a href="{{ route('services.create') }}" class="btn-primary">
        <x-heroicon-o-plus class="w-4 h-4" />
        Tambah layanan
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

{{-- Pencarian --}}
<form method="GET" action="{{ route('services.index') }}" class="flex items-center gap-2 mb-5">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Cari layanan..." class="input !w-56 !py-1.5 text-xs">
    <button type="submit" class="btn-primary !py-1.5 !text-xs">Cari</button>
    @if (request('search'))
        <a href="{{ route('services.index') }}" class="btn-secondary !py-1.5 !text-xs">Reset</a>
    @endif
</form>

{{-- Layanan digital --}}
<div class="mb-3">
    <h3 class="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
        Layanan Digital
    </h3>
</div>

@forelse ($groups as $group)
    @php($category = $group['category'])
    <div class="card mb-4">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {{ $category->name }}
            </h4>
            <span class="badge-neutral">{{ $group['items']->count() }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
            @foreach ($group['items'] as $service)
                @include('dashboard.services._item', ['service' => $service])
            @endforeach
        </div>
    </div>
@empty
    @if ($uncategorized->isEmpty())
        <div class="card py-12 text-center">
            <p class="text-xs text-neutral-400">Belum ada layanan. Tambahkan lewat tombol di atas.</p>
        </div>
    @endif
@endforelse

{{-- Layanan tanpa kategori --}}
@if ($uncategorized->isNotEmpty())
    <div class="card mb-4">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Lainnya</h4>
            <span class="badge-neutral">{{ $uncategorized->count() }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
            @foreach ($uncategorized as $service)
                @include('dashboard.services._item', ['service' => $service])
            @endforeach
        </div>
    </div>
@endif

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
                                   @input="nominal = parseInt($event.target.value.replace(/\D/g,'')) || 0; computeFee()"
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
                        <p class="text-[11px] text-neutral-400 mt-1">Terisi otomatis dari tarif; bisa diubah. Fee = profit konter.</p>
                    </div>

                    {{-- Rekening tujuan + efek saldo (untuk tarik/setor) --}}
                    <template x-if="movesCash">
                        <div class="space-y-2">
                            <div>
                                <label class="label">Rekening terdampak</label>
                                <template x-if="bankAccounts.length > 0">
                                    <select x-model="bank_account_id" name="bank_account_id" class="select w-full">
                                        <option value="">-- Pilih bank / e-wallet --</option>
                                        <template x-for="b in bankAccounts" :key="b.id">
                                            <option :value="b.id" x-text="b.type_label + ' · ' + b.bank_name + ' · ' + b.account_number"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="bankAccounts.length === 0">
                                    <a href="{{ route('banks.index') }}" class="block text-[11px] text-amber-600 dark:text-amber-400 px-3 py-2 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
                                        Belum ada rekening. Tambah dulu di menu Saldo bank.
                                    </a>
                                </template>
                            </div>

                            <div class="text-[11px] px-3 py-2 rounded-lg bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 space-y-0.5">
                                <div class="flex justify-between">
                                    <span class="text-neutral-500">Saldo bank</span>
                                    <span :class="svc.cash_direction === 'tarik' ? 'text-red-500' : 'text-primary-600 dark:text-primary-400'"
                                          x-text="(svc.cash_direction === 'tarik' ? '− ' : '+ ') + 'Rp ' + nominal.toLocaleString('id-ID')"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-neutral-500">Kas fisik</span>
                                    <span :class="cashEffect >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-red-500'"
                                          x-text="(cashEffect >= 0 ? '+ ' : '− ') + 'Rp ' + Math.abs(cashEffect).toLocaleString('id-ID')"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Jasa servis: qty --}}
            <template x-if="svc.kind === 'servis'">
                <div>
                    <label class="label">Jumlah</label>
                    <input type="number" name="qty" x-model.number="qty" min="1" class="input">
                </div>
            </template>

            {{-- Eceran: harga jual + modal --}}
            <template x-if="svc.kind === 'eceran'">
                <div class="space-y-3">
                    <div>
                        <label class="label">Harga jual (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                            <input type="text" :value="formatNum(sell_price)"
                                   @input="sell_price = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                   class="input pl-9" placeholder="mis: 20.000">
                            <input type="hidden" name="price" :value="sell_price">
                        </div>
                    </div>
                    <div>
                        <label class="label">Harga modal (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                            <input type="text" :value="formatNum(cost)"
                                   @input="cost = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                   class="input pl-9" placeholder="0">
                            <input type="hidden" name="cost_price" :value="cost">
                        </div>
                    </div>
                    <input type="hidden" name="qty" value="1">
                    <div class="flex items-center justify-between text-xs px-3 py-2 rounded-lg
                                bg-neutral-50 dark:bg-neutral-800
                                border border-neutral-200 dark:border-neutral-700">
                        <span class="text-neutral-500">Profit</span>
                        <span class="font-medium"
                              :class="eceranProfit >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-red-500'"
                              x-text="'Rp ' + eceranProfit.toLocaleString('id-ID')"></span>
                    </div>
                </div>
            </template>

            {{-- Rita: jumlah voucher + modal total + harga total --}}
            <template x-if="svc.kind === 'rita'">
                <div class="space-y-3">
                    <div class="text-[11px] px-3 py-2 rounded-lg bg-neutral-50 dark:bg-neutral-800
                                border border-neutral-200 dark:border-neutral-700 flex justify-between">
                        <span class="text-neutral-500">Saldo Rita</span>
                        <span x-text="'Rp ' + (svc.rita_balance || 0).toLocaleString('id-ID')"></span>
                    </div>
                    <div>
                        <label class="label">Jumlah voucher</label>
                        <input type="number" name="qty" x-model.number="qty" min="1"
                               :max="svc.product_stock" class="input">
                        <p class="text-[11px] mt-1"
                           :class="qty > (svc.product_stock || 0) ? 'text-red-500' : 'text-neutral-400'"
                           x-text="'Stok voucher: ' + (svc.product_stock || 0) + ' pcs' + (qty > (svc.product_stock||0) ? ' — tidak cukup' : '')"></p>
                    </div>
                    <div>
                        <label class="label">Modal total (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                            <input type="text" :value="formatNum(cost)"
                                   @input="cost = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                   class="input pl-9" placeholder="mis: 15.000">
                            <input type="hidden" name="cost_price" :value="cost">
                        </div>
                        <p class="text-[11px] mt-1"
                           :class="cost > (svc.rita_balance || 0) ? 'text-red-500' : 'text-neutral-400'"
                           x-text="cost > (svc.rita_balance||0) ? 'Melebihi saldo Rita' : 'Memotong saldo Rita'"></p>
                    </div>
                    <div>
                        <label class="label">Harga total (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                            <input type="text" :value="formatNum(sell_price)"
                                   @input="sell_price = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                   class="input pl-9" placeholder="mis: 18.000">
                            <input type="hidden" name="price" :value="sell_price">
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs px-3 py-2 rounded-lg
                                bg-neutral-50 dark:bg-neutral-800
                                border border-neutral-200 dark:border-neutral-700">
                        <span class="text-neutral-500">Profit</span>
                        <span class="font-medium"
                              :class="eceranProfit >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-red-500'"
                              x-text="'Rp ' + eceranProfit.toLocaleString('id-ID')"></span>
                    </div>
                </div>
            </template>

            <div x-show="!movesCash">
                <label class="label">Metode bayar</label>
                <select name="payment_method" x-model="payment_method" class="select" :disabled="movesCash">
                    <option value="cash">Tunai</option>
                    <option value="transfer">Transfer</option>
                </select>
            </div>

            {{-- Transfer biasa: rekening tujuan (bank / e-wallet) --}}
            <div x-show="payment_method === 'transfer' && !movesCash" x-cloak>
                <label class="label">Transfer masuk ke</label>
                <template x-if="bankAccounts.length > 0">
                    <select x-model="bank_account_id" name="bank_account_id" class="select w-full">
                        <option value="">-- Pilih bank / e-wallet --</option>
                        <template x-for="b in bankAccounts" :key="b.id">
                            <option :value="b.id" x-text="b.type_label + ' · ' + b.bank_name + ' · ' + b.account_number"></option>
                        </template>
                    </select>
                </template>
                <template x-if="bankAccounts.length === 0">
                    <a href="{{ route('banks.index') }}" class="block text-[11px] text-amber-600 dark:text-amber-400 px-3 py-2 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
                        Belum ada rekening. Tambah dulu di menu Saldo bank.
                    </a>
                </template>
            </div>

            <div>
                <label class="label">Catatan</label>
                <textarea name="note" rows="2" class="input resize-none"
                          placeholder="Opsional..."></textarea>
            </div>

            {{-- Rincian nominal + fee (jasa keuangan fee-saja) --}}
            <div x-show="svc.kind === 'keuangan' && !movesCash" x-cloak
                 class="text-[11px] text-neutral-500 space-y-0.5 px-1">
                <div class="flex justify-between">
                    <span>Nominal (modal)</span>
                    <span x-text="'Rp ' + nominal.toLocaleString('id-ID')"></span>
                </div>
                <div class="flex justify-between">
                    <span>Fee (profit)</span>
                    <span x-text="'Rp ' + fee.toLocaleString('id-ID')"></span>
                </div>
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
            <button type="submit" :disabled="!canSell"
                    class="btn-primary flex-1 justify-center !text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                <x-heroicon-o-check class="w-3.5 h-3.5" />
                Catat
            </button>
        </div>
    </form>
</div>

</div>

@endsection
