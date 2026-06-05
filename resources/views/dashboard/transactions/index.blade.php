@extends('layouts.app')
@section('title', 'Transaksi')

@section('content')
<div x-data="transaksiPage({{ Js::from($products) }})">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Transaksi</h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Riwayat penjualan cabang</p>
        </div>
        @if ($activeShift)
            <button type="button" @click="openKasir()" class="btn-primary">
                <x-heroicon-o-shopping-cart class="w-4 h-4" /> Buka Kasir
            </button>
        @else
            <a href="{{ route('shifts.index') }}" class="btn-secondary">
                <x-heroicon-o-lock-closed class="w-4 h-4" /> Buka shift dulu
            </a>
        @endif
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
        <div class="stat-card">
            <span class="stat-label">Penjualan hari ini</span>
            <p class="stat-value text-base">Rp {{ number_format($todaySales, 0, ',', '.') }}</p>
        </div>
        <div class="stat-card">
            <span class="stat-label">Transaksi hari ini</span>
            <p class="stat-value">{{ $todayCount }}</p>
        </div>
        <div class="stat-card">
            <span class="stat-label">Status shift</span>
            <p class="stat-value text-base">{{ $activeShift ? 'Aktif' : 'Tutup' }}</p>
        </div>
    </div>

    {{-- Riwayat --}}
    <div class="card p-0 overflow-hidden">
        <form method="GET" action="{{ route('transactions.index') }}"
              class="flex flex-wrap items-center gap-2 px-4 py-3 border-b border-neutral-200 dark:border-neutral-800">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no. transaksi..." class="input !w-48 !py-1.5 text-xs">
            <input type="date" name="date" value="{{ request('date') }}" class="input !w-40 !py-1.5 text-xs">
            <select name="status" class="select !w-32 !py-1.5 text-xs">
                <option value="">Semua status</option>
                <option value="completed" @selected(request('status')==='completed')>Selesai</option>
                <option value="void" @selected(request('status')==='void')>Batal</option>
            </select>
            <button type="submit" class="btn-primary !py-1.5 !text-xs">Filter</button>
            @if (request()->hasAny(['search','date','status']))
                <a href="{{ route('transactions.index') }}" class="btn-secondary !py-1.5 !text-xs">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>No. Transaksi</th><th>Waktu</th><th>Kasir</th>
                        <th class="text-right">Total</th><th class="text-center">Bayar</th>
                        <th class="text-center">Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Baris baru via AJAX --}}
                    <template x-for="r in recent" :key="r.id">
                        <tr class="bg-primary-50/40 dark:bg-primary-900/10">
                            <td class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                                <span x-text="r.trx_number"></span>
                                <span class="badge-primary ml-1">baru</span>
                            </td>
                            <td class="text-xs text-neutral-500" x-text="r.created_at"></td>
                            <td class="text-xs text-neutral-500" x-text="r.kasir"></td>
                            <td class="text-right text-xs font-medium" x-text="rupiah(r.total)"></td>
                            <td class="text-center text-xs text-neutral-500" x-text="r.payment_label"></td>
                            <td class="text-center">
                                <span x-show="r.status==='completed'" class="badge-success">Selesai</span>
                                <span x-show="r.status==='void'" x-cloak class="badge-danger">Batal</span>
                            </td>
                            <td class="text-right">
                                <button type="button" @click="openDetail(r.id)" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Detail</button>
                            </td>
                        </tr>
                    </template>

                    @forelse ($transactions as $trx)
                        <tr>
                            <td class="text-xs font-medium text-neutral-900 dark:text-neutral-100">{{ $trx->trx_number }}</td>
                            <td class="text-xs text-neutral-500">{{ $trx->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="text-xs text-neutral-500">{{ $trx->kasir?->name ?? '-' }}</td>
                            <td class="text-right text-xs font-medium">Rp {{ number_format($trx->total, 0, ',', '.') }}</td>
                            <td class="text-center text-xs text-neutral-500">{{ $trx->payment_label }}</td>
                            <td class="text-center">
                                @if ($trx->status === 'completed') <span class="badge-success">Selesai</span>
                                @elseif ($trx->status === 'void') <span class="badge-danger">Batal</span>
                                @else <span class="badge-neutral">{{ $trx->status }}</span> @endif
                            </td>
                            <td class="text-right">
                                <button type="button" @click="openDetail('{{ $trx->id }}')" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr x-show="recent.length === 0">
                            <td colspan="7" class="py-10 text-center text-xs text-neutral-400">Belum ada transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transactions->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200 dark:border-neutral-800">{{ $transactions->links() }}</div>
        @endif
    </div>

    {{-- ============ TOAST ============ --}}
    <div x-cloak x-show="toast.show"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 -translate-y-1"
         class="fixed top-4 right-4 z-[60] flex items-center gap-3 px-4 py-3 rounded-xl text-sm shadow-lg border"
         :class="toast.type === 'success'
            ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-200 dark:border-primary-800 text-primary-800 dark:text-primary-200'
            : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'">
        <span x-text="toast.msg"></span>
        <button type="button" @click="toast.show = false">
            <x-heroicon-s-x-mark class="w-4 h-4 opacity-60 hover:opacity-100" />
        </button>
    </div>

    {{-- ============ MODAL KASIR ============ --}}
    <div x-cloak x-show="kasirOpen" @click.self="kasirOpen = false" @keydown.escape.window="kasirOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-black/50">
        <div class="w-[97vw] max-w-6xl h-[92vh] bg-neutral-50 dark:bg-neutral-950 rounded-2xl overflow-hidden flex flex-col border border-neutral-200 dark:border-neutral-800">
            <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-neutral-900 border-b border-neutral-200 dark:border-neutral-800 shrink-0">
                <h3 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Kasir</h3>
                <button type="button" @click="kasirOpen = false" class="text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            <div class="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-3 p-3 overflow-hidden">
                {{-- Produk --}}
                <div class="lg:col-span-2 flex flex-col min-h-0">
                    <div class="card flex flex-col min-h-0 flex-1 space-y-3">
                        <div class="flex flex-wrap gap-2 shrink-0">
                            <div class="relative flex-1 min-w-48">
                                <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none" />
                                <input type="text" x-model="search" placeholder="Cari produk / SKU..." class="input pl-9">
                            </div>
                            <select x-model="categoryFilter" class="select !w-44">
                                <option value="">Semua kategori</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 overflow-y-auto">
                            <template x-for="p in filteredProducts" :key="p.id">
                                <button type="button" @click="addToCart(p)" :disabled="p.stock <= 0"
                                    class="text-left p-3 rounded-xl border border-neutral-200 dark:border-neutral-700 hover:border-primary-400 dark:hover:border-primary-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                    <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100 line-clamp-2" x-text="p.name"></p>
                                    <p class="text-[11px] text-neutral-400 mt-0.5" x-text="p.category || '-'"></p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-xs font-semibold text-primary-600 dark:text-primary-400" x-text="rupiah(p.price)"></span>
                                        <span class="text-[10px]" :class="p.stock <= 0 ? 'text-red-500' : 'text-neutral-400'" x-text="p.stock <= 0 ? 'Habis' : ('Stok ' + p.stock)"></span>
                                    </div>
                                </button>
                            </template>
                            <div x-show="filteredProducts.length === 0" class="col-span-full py-10 text-center text-xs text-neutral-400">Produk tidak ditemukan</div>
                        </div>
                    </div>
                </div>

                {{-- Keranjang --}}
                <div class="flex flex-col min-h-0">
                    <div class="card flex flex-col min-h-0 flex-1 space-y-3 overflow-y-auto">
                        <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100 flex items-center justify-between shrink-0">
                            Keranjang <span class="text-neutral-400" x-text="cart.length + ' item'"></span>
                        </h3>

                        <div class="space-y-2 flex-1">
                            <template x-for="(item, i) in cart" :key="item.id">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100 truncate" x-text="item.name"></p>
                                        <p class="text-[11px] text-neutral-400" x-text="rupiah(item.price)"></p>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="dec(i)" class="w-6 h-6 rounded border border-neutral-200 dark:border-neutral-700 text-neutral-500">−</button>
                                        <input type="number" min="1" :max="item.stock" x-model.number="item.qty" @input="clampQty(i)"
                                               class="w-10 text-center text-xs border border-neutral-200 dark:border-neutral-700 rounded py-1 bg-transparent">
                                        <button type="button" @click="inc(i)" class="w-6 h-6 rounded border border-neutral-200 dark:border-neutral-700 text-neutral-500">+</button>
                                    </div>
                                    <button type="button" @click="removeItem(i)" class="text-neutral-400 hover:text-red-500">
                                        <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </template>
                            <div x-show="cart.length === 0" class="py-8 text-center text-xs text-neutral-400">Keranjang kosong</div>
                        </div>

                        <div class="pt-2 border-t border-neutral-100 dark:border-neutral-800 space-y-2 shrink-0">
                            <label class="label">Diskon</label>
                            <div class="flex gap-2">
                                <select x-model="discount_type" @change="discount_value = 0; discountK = ''" class="select !w-28">
                                    <option value="none">Tanpa</option>
                                    <option value="percent">Persen %</option>
                                    <option value="nominal">Nominal</option>
                                </select>

                                {{-- Tanpa diskon --}}
                                <input x-show="discount_type === 'none'" type="text" disabled class="input flex-1 opacity-50" placeholder="0">

                                {{-- Persen --}}
                                <div x-show="discount_type === 'percent'" x-cloak class="relative flex-1">
                                    <input type="number" min="0" max="100" x-model.number="discount_value" class="input pr-7" placeholder="0">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-neutral-500 pointer-events-none">%</span>
                                </div>

                                {{-- Nominal (ketik angka = ribuan, ×1000) --}}
                                <input x-show="discount_type === 'nominal'" x-cloak
                                       type="text" inputmode="numeric"
                                       :value="discountK ? numID(discountK) : ''"
                                       @input="discountK = $event.target.value.replace(/\D/g,''); discount_value = (parseInt(discountK)||0)*1000"
                                       @keydown="if(['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight'].includes($event.key))return; if(!/[0-9]/.test($event.key))$event.preventDefault();"
                                       class="input flex-1" placeholder="0">
                            </div>
                            <p x-show="discount_type === 'nominal' && discount_value > 0" x-cloak class="text-[11px] text-neutral-500">
                                = <span class="font-medium text-neutral-900 dark:text-neutral-100" x-text="rupiah(discount_value)"></span>
                                <span class="text-neutral-400">(ketik angka dalam ribuan)</span>
                            </p>
                        </div>

                        <div class="pt-2 border-t border-neutral-100 dark:border-neutral-800 space-y-1 text-xs shrink-0">
                            <div class="flex justify-between text-neutral-500"><span>Subtotal</span><span x-text="rupiah(subtotal)"></span></div>
                            <div class="flex justify-between text-neutral-500" x-show="discountAmount > 0"><span>Diskon</span><span x-text="'− ' + rupiah(discountAmount)"></span></div>
                            <div class="flex justify-between text-sm font-semibold text-neutral-900 dark:text-neutral-100 pt-1"><span>Total</span><span x-text="rupiah(total)"></span></div>
                        </div>

                        <div class="pt-2 border-t border-neutral-100 dark:border-neutral-800 space-y-2 shrink-0">
                            <label class="label">Metode bayar</label>
                            <div class="grid grid-cols-3 gap-1">
                                <template x-for="m in [{v:'cash',l:'Tunai'},{v:'transfer',l:'Transfer'},{v:'mixed',l:'Campur'}]" :key="m.v">
                                    <button type="button" @click="payment_method = m.v"
                                        class="text-xs py-1.5 rounded-lg border transition-colors"
                                        :class="payment_method === m.v ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium' : 'border-neutral-200 dark:border-neutral-700 text-neutral-500'"
                                        x-text="m.l"></button>
                                </template>
                            </div>
                            <div x-show="payment_method === 'cash' || payment_method === 'mixed'">
                                <label class="label">Tunai</label>
                                <input type="text" inputmode="numeric"
                                       :value="paid_cash ? numID(paid_cash) : ''"
                                       @input="paid_cash = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                       @keydown="if(['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight'].includes($event.key))return; if(!/[0-9]/.test($event.key))$event.preventDefault();"
                                       class="input" placeholder="0">
                            </div>
                            <div x-show="payment_method === 'transfer' || payment_method === 'mixed'">
                                <label class="label">Transfer</label>
                                <input type="text" inputmode="numeric"
                                       :value="paid_transfer ? numID(paid_transfer) : ''"
                                       @input="paid_transfer = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                       @keydown="if(['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight'].includes($event.key))return; if(!/[0-9]/.test($event.key))$event.preventDefault();"
                                       class="input" placeholder="0">
                            </div>
                            <div class="flex justify-between text-xs pt-1" :class="change >= 0 ? 'text-neutral-500' : 'text-red-500'">
                                <span x-text="change >= 0 ? 'Kembalian' : 'Kurang'"></span>
                                <span x-text="rupiah(Math.abs(change))"></span>
                            </div>
                        </div>

                        <button type="button" @click="submit()" :disabled="!canPay || submitting"
                            class="btn-primary w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed shrink-0">
                            <x-heroicon-o-banknotes class="w-4 h-4" />
                            <span x-show="!submitting" x-text="'Bayar · ' + rupiah(total)"></span>
                            <span x-show="submitting" x-cloak>Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ MODAL STRUK ============ --}}
    <div x-cloak x-show="receiptOpen" @click.self="receiptOpen = false" @keydown.escape.window="receiptOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="w-full max-w-md max-h-[90vh] overflow-y-auto bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-2xl">
            <div class="px-5 py-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100" x-text="receipt.trx_number"></p>
                    <p class="text-[11px] text-neutral-400" x-text="receipt.created_at + ' · ' + receipt.kasir"></p>
                </div>
                <span x-show="receipt.status==='completed'" class="badge-success">Selesai</span>
                <span x-show="receipt.status==='void'" x-cloak class="badge-danger">Batal</span>
            </div>

            <div class="px-5 py-4 space-y-3 text-xs">
                <template x-for="(it, i) in receipt.items" :key="i">
                    <div class="flex justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-medium text-neutral-900 dark:text-neutral-100 truncate" x-text="it.name"></p>
                            <p class="text-neutral-400" x-text="it.qty + ' × ' + rupiah(it.unit_price)"></p>
                        </div>
                        <span class="text-neutral-700 dark:text-neutral-300 shrink-0" x-text="rupiah(it.subtotal)"></span>
                    </div>
                </template>

                <div class="border-t border-neutral-100 dark:border-neutral-800 pt-2 space-y-1">
                    <div class="flex justify-between text-neutral-500"><span>Subtotal</span><span x-text="rupiah(receipt.subtotal)"></span></div>
                    <div class="flex justify-between text-neutral-500" x-show="receipt.discount_amount > 0">
                        <span>Diskon <span x-show="receipt.discount_type==='percent'" x-text="'(' + receipt.discount_value + '%)'"></span></span>
                        <span x-text="'− ' + rupiah(receipt.discount_amount)"></span>
                    </div>
                    <div class="flex justify-between text-sm font-semibold text-neutral-900 dark:text-neutral-100 pt-1"><span>Total</span><span x-text="rupiah(receipt.total)"></span></div>
                    <div class="flex justify-between text-neutral-500 pt-1"><span>Tunai</span><span x-text="rupiah(receipt.paid_cash)"></span></div>
                    <div class="flex justify-between text-neutral-500"><span>Transfer</span><span x-text="rupiah(receipt.paid_transfer)"></span></div>
                    <div class="flex justify-between text-neutral-500"><span>Kembalian</span><span x-text="rupiah(receipt.change_amount)"></span></div>
                </div>
                <p x-show="receipt.void_reason" x-cloak class="text-red-500" x-text="'Alasan batal: ' + receipt.void_reason"></p>
            </div>

            <div class="px-5 py-4 border-t border-neutral-200 dark:border-neutral-800 flex gap-2">
                <button type="button" @click="receiptOpen = false" class="btn-secondary flex-1 justify-center !text-xs">Tutup</button>
                <button type="button" x-show="receipt.status==='completed'" @click="voidOpen = true; voidReason = ''" class="btn-danger flex-1 justify-center !text-xs">
                    Batalkan
                </button>
            </div>
        </div>
    </div>

    {{-- ============ MODAL KONFIRMASI VOID ============ --}}
    <div x-cloak x-show="voidOpen" @keydown.escape.window="voidOpen = false"
         class="fixed inset-0 z-[55] flex items-center justify-center p-4 bg-black/50">
        <div class="w-full max-w-sm bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 space-y-2">
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Batalkan transaksi?</p>
                <p class="text-xs text-neutral-500">Stok produk akan dikembalikan. Tindakan ini tercatat.</p>
                <textarea x-model="voidReason" rows="2" class="input resize-none" placeholder="Alasan pembatalan..."></textarea>
            </div>
            <div class="flex gap-2 px-5 py-4 border-t border-neutral-200 dark:border-neutral-800">
                <button type="button" @click="voidOpen = false" class="btn-secondary flex-1 justify-center !text-xs">Batal</button>
                <button type="button" @click="confirmVoid()" class="btn-danger flex-1 justify-center !text-xs">Ya, batalkan</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('transaksiPage', (products) => ({
        products,
        detailUrl: '{{ url('transaksi') }}',
        storeUrl: '{{ route('transactions.store') }}',

        // state kasir
        search: '', categoryFilter: '', cart: [],
        discount_type: 'none', discount_value: 0, discountK: '',
        payment_method: 'cash', paid_cash: 0, paid_transfer: 0, note: '',

        // ui
        kasirOpen: false, receiptOpen: false, voidOpen: false,
        voidReason: '', submitting: false,
        receipt: { items: [], status: '' },
        recent: [],
        toast: { show: false, type: 'success', msg: '' },
        _t: null,

        get filteredProducts() {
            const s = this.search.toLowerCase();
            return this.products.filter(p => {
                const ms = !s || p.name.toLowerCase().includes(s) || (p.sku || '').toLowerCase().includes(s);
                const mc = !this.categoryFilter || p.category_id === this.categoryFilter;
                return ms && mc;
            });
        },
        get subtotal() { return this.cart.reduce((s, i) => s + i.price * i.qty, 0); },
        get discountAmount() {
            if (this.discount_type === 'percent') return Math.round(this.subtotal * Math.min(this.discount_value || 0, 100) / 100);
            if (this.discount_type === 'nominal') return Math.min(this.discount_value || 0, this.subtotal);
            return 0;
        },
        get total() { return Math.max(this.subtotal - this.discountAmount, 0); },
        get paid() {
            const c = this.payment_method === 'transfer' ? 0 : (this.paid_cash || 0);
            const t = this.payment_method === 'cash' ? 0 : (this.paid_transfer || 0);
            return c + t;
        },
        get change() { return this.paid - this.total; },
        get canPay() { return this.cart.length > 0 && this.total > 0 && this.change >= 0; },

        openKasir() { this.resetCart(); this.kasirOpen = true; },
        addToCart(p) {
            if (p.stock <= 0) return;
            const ex = this.cart.find(i => i.id === p.id);
            if (ex) { if (ex.qty < p.stock) ex.qty++; }
            else { this.cart.push({ id: p.id, name: p.name, price: p.price, stock: p.stock, qty: 1 }); }
        },
        inc(i) { if (this.cart[i].qty < this.cart[i].stock) this.cart[i].qty++; },
        dec(i) { if (this.cart[i].qty > 1) this.cart[i].qty--; },
        clampQty(i) { let q = parseInt(this.cart[i].qty) || 1; this.cart[i].qty = Math.max(1, Math.min(q, this.cart[i].stock)); },
        removeItem(i) { this.cart.splice(i, 1); },
        resetCart() {
            this.cart = []; this.discount_type = 'none'; this.discount_value = 0; this.discountK = '';
            this.payment_method = 'cash'; this.paid_cash = 0; this.paid_transfer = 0;
            this.note = ''; this.search = ''; this.categoryFilter = '';
        },
        rupiah(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0); },
        numID(n) { return new Intl.NumberFormat('id-ID').format(n || 0); },

        notify(type, msg) {
            this.toast = { show: true, type, msg };
            clearTimeout(this._t);
            this._t = setTimeout(() => { this.toast.show = false; }, 3500);
        },

        _headers() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            };
        },

        async submit() {
            if (!this.canPay || this.submitting) return;
            this.submitting = true;
            try {
                const res = await fetch(this.storeUrl, {
                    method: 'POST', headers: this._headers(),
                    body: JSON.stringify({
                        items: this.cart.map(i => ({ product_id: i.id, qty: i.qty })),
                        discount_type: this.discount_type,
                        discount_value: this.discount_type === 'none' ? 0 : (this.discount_value || 0),
                        payment_method: this.payment_method,
                        paid_cash: this.payment_method === 'transfer' ? 0 : (this.paid_cash || 0),
                        paid_transfer: this.payment_method === 'cash' ? 0 : (this.paid_transfer || 0),
                        note: this.note || null,
                    }),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    const msg = data.message || (data.errors ? Object.values(data.errors)[0][0] : 'Gagal menyimpan transaksi.');
                    this.notify('error', msg);
                    return;
                }
                this.receipt = data.receipt;
                this.recent.unshift({
                    id: data.receipt.id, trx_number: data.receipt.trx_number,
                    created_at: data.receipt.created_at, kasir: data.receipt.kasir,
                    total: data.receipt.total, payment_label: data.receipt.payment_label,
                    status: data.receipt.status,
                });
                this.resetCart();
                this.kasirOpen = false;
                this.receiptOpen = true;
                this.notify('success', 'Transaksi ' + data.receipt.trx_number + ' berhasil disimpan.');
            } catch (e) {
                this.notify('error', 'Terjadi kesalahan jaringan.');
            } finally {
                this.submitting = false;
            }
        },

        async openDetail(id) {
            try {
                const res = await fetch(this.detailUrl + '/' + id, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.ok) { this.receipt = data.receipt; this.receiptOpen = true; }
                else this.notify('error', 'Gagal memuat detail.');
            } catch (e) { this.notify('error', 'Gagal memuat detail.'); }
        },

        async confirmVoid() {
            if (!this.voidReason.trim()) { this.notify('error', 'Alasan wajib diisi.'); return; }
            try {
                const res = await fetch(this.detailUrl + '/' + this.receipt.id + '/void', {
                    method: 'POST', headers: this._headers(),
                    body: JSON.stringify({ void_reason: this.voidReason }),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) { this.notify('error', data.message || 'Gagal membatalkan.'); return; }
                this.receipt.status = 'void';
                const r = this.recent.find(x => x.id === this.receipt.id);
                if (r) r.status = 'void';
                this.voidOpen = false; this.voidReason = '';
                this.notify('success', 'Transaksi dibatalkan & stok dikembalikan.');
            } catch (e) { this.notify('error', 'Terjadi kesalahan jaringan.'); }
        },
    }));
});
</script>
@endpush
