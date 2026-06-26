@extends('layouts.app')
@section('title', 'Tambah Jasa')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('services.index') }}" class="btn-secondary !text-xs !py-1.5">
        <x-heroicon-o-arrow-left class="w-3.5 h-3.5" />
        Kembali
    </a>
    <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Tambah Jasa</h2>
</div>

@php
    $oldTiers = old('fee_tiers', [['max' => '', 'fee' => '']]);
@endphp
<form method="POST" action="{{ route('services.store') }}"
      x-data="{
          kind:           '{{ old('kind', 'servis') }}',
          price:           {{ (int) old('price', 0) }},
          cost_price:      {{ (int) old('cost_price', 0) }},
          default_fee:     {{ (int) old('default_fee', 0) }},
          cash_direction: '{{ old('cash_direction', 'none') }}',
          rita_balance:    {{ (int) old('rita_balance', 0) }},
          tiers:           {{ Js::from($oldTiers) }},
          addTier() { this.tiers.push({ max: '', fee: '' }) },
          removeTier(i) { this.tiers.splice(i, 1); if (this.tiers.length === 0) this.addTier() },
          formatNum(val) {
              if (!val || val == 0) return ''
              return new Intl.NumberFormat('id-ID').format(val)
          },
          margin() { return this.price - this.cost_price }
      }">
    @csrf

    <div class="space-y-4">

        {{-- Jenis jasa --}}
        <div class="card space-y-3">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Jenis jasa</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-start gap-2.5 p-3 rounded-xl cursor-pointer border
                              transition-colors duration-150"
                       :class="kind === 'servis'
                           ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/20'
                           : 'border-neutral-200 dark:border-neutral-700'">
                    <input type="radio" name="kind" value="servis" x-model="kind"
                           class="mt-0.5 accent-primary-600">
                    <span>
                        <span class="block text-xs font-medium text-neutral-900 dark:text-neutral-100">Jasa servis</span>
                        <span class="block text-[11px] text-neutral-500 mt-0.5">Harga tetap (servis HP, pasang antigores)</span>
                    </span>
                </label>
                <label class="flex items-start gap-2.5 p-3 rounded-xl cursor-pointer border
                              transition-colors duration-150"
                       :class="kind === 'keuangan'
                           ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/20'
                           : 'border-neutral-200 dark:border-neutral-700'">
                    <input type="radio" name="kind" value="keuangan" x-model="kind"
                           class="mt-0.5 accent-primary-600">
                    <span>
                        <span class="block text-xs font-medium text-neutral-900 dark:text-neutral-100">Jasa keuangan</span>
                        <span class="block text-[11px] text-neutral-500 mt-0.5">Nominal + fee (tarik tunai, transfer, token)</span>
                    </span>
                </label>
                <label class="flex items-start gap-2.5 p-3 rounded-xl cursor-pointer border
                              transition-colors duration-150"
                       :class="kind === 'eceran'
                           ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/20'
                           : 'border-neutral-200 dark:border-neutral-700'">
                    <input type="radio" name="kind" value="eceran" x-model="kind"
                           class="mt-0.5 accent-primary-600">
                    <span>
                        <span class="block text-xs font-medium text-neutral-900 dark:text-neutral-100">Eceran</span>
                        <span class="block text-[11px] text-neutral-500 mt-0.5">Harga jual &amp; modal diketik saat transaksi (Pertalite/BBM)</span>
                    </span>
                </label>
                <label class="flex items-start gap-2.5 p-3 rounded-xl cursor-pointer border
                              transition-colors duration-150"
                       :class="kind === 'rita'
                           ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/20'
                           : 'border-neutral-200 dark:border-neutral-700'">
                    <input type="radio" name="kind" value="rita" x-model="kind"
                           class="mt-0.5 accent-primary-600">
                    <span>
                        <span class="block text-xs font-medium text-neutral-900 dark:text-neutral-100">Rita</span>
                        <span class="block text-[11px] text-neutral-500 mt-0.5">Nembak voucher — saldo deposit + stok produk</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Informasi --}}
        <div class="card space-y-4">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Informasi jasa</h3>

            <div>
                <label class="label">Nama jasa <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="input @error('name') border-red-400 @enderror"
                       placeholder="mis: Tarik tunai / Servis ganti LCD" required>
                @error('name')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="{
                    open: false, name: '', error: '', loading: false,
                    openModal() { this.open = true; this.error = ''; this.name = ''; this.$nextTick(() => this.$refs.nameInput?.focus()) },
                    async submit() {
                        if (this.loading) return
                        this.error = ''
                        if (!this.name.trim()) { this.error = 'Nama kategori wajib diisi.'; return }
                        this.loading = true
                        try {
                            const res = await fetch('{{ route('categories.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                },
                                body: JSON.stringify({ name: this.name.trim(), type: 'service' })
                            })
                            const data = await res.json()
                            if (!res.ok) {
                                this.error = (data.errors && data.errors.name) ? data.errors.name[0] : (data.message || 'Gagal menyimpan kategori.')
                                return
                            }
                            window.dispatchEvent(new CustomEvent('category-added', { detail: data }))
                            this.name = ''; this.open = false
                        } catch (e) { this.error = 'Terjadi kesalahan jaringan.' }
                        finally { this.loading = false }
                    }
                }">
                <div class="flex items-center justify-between mb-1">
                    <label class="label !mb-0">Kategori</label>
                    <button type="button" @click="openModal()"
                            class="inline-flex items-center gap-1 text-[11px] font-medium
                                   text-primary-600 dark:text-primary-400 hover:underline">
                        <x-heroicon-o-plus class="w-3 h-3" />
                        Tambah kategori
                    </button>
                </div>

                <x-category-select :categories="$categories"/>

                <div x-cloak x-show="open" @keydown.escape.window="open = false" x-transition.opacity
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                    <div @click.outside="open = false"
                         class="w-full max-w-sm bg-white dark:bg-neutral-900
                                border border-neutral-200 dark:border-neutral-800 rounded-2xl overflow-hidden">
                        <div class="px-5 py-4">
                            <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">Tambah kategori jasa</p>
                            <p class="text-xs text-neutral-500 mb-3">Kategori dibuat untuk cabang yang sedang aktif.</p>
                            <input type="text" x-model="name" x-ref="nameInput"
                                   @keydown.enter.prevent="submit()" maxlength="255"
                                   placeholder="mis: Pembayaran" class="input" :class="error ? 'border-red-400' : ''">
                            <p x-show="error" x-cloak x-text="error" class="text-xs text-red-500 mt-1"></p>
                        </div>
                        <div class="flex gap-2 px-5 py-4 border-t border-neutral-200 dark:border-neutral-800">
                            <button type="button" @click="open = false" class="btn-secondary flex-1 justify-center !text-xs">Batal</button>
                            <button type="button" @click="submit()" :disabled="loading"
                                    class="btn-primary flex-1 justify-center !text-xs disabled:opacity-60">
                                <x-heroicon-o-check class="w-3.5 h-3.5" />
                                <span x-show="!loading">Simpan</span>
                                <span x-show="loading" x-cloak>Menyimpan...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="label">Catatan</label>
                <textarea name="note" rows="2" class="input resize-none" placeholder="Opsional...">{{ old('note') }}</textarea>
            </div>
        </div>

        {{-- Harga (servis) --}}
        <div class="card space-y-4" x-show="kind === 'servis'">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Harga</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Harga modal (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-neutral-500 pointer-events-none">Rp</span>
                        <input type="text" :value="formatNum(cost_price)"
                               @input="cost_price = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                               class="input pl-9" placeholder="0">
                        <input type="hidden" name="cost_price" :value="cost_price">
                    </div>
                </div>
                <div>
                    <label class="label">Harga jual (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-neutral-500 pointer-events-none">Rp</span>
                        <input type="text" :value="formatNum(price)"
                               @input="price = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                               class="input pl-9" placeholder="0">
                        <input type="hidden" name="price" :value="price">
                    </div>
                </div>
            </div>
            <div x-show="cost_price > 0 && price > 0"
                 class="flex items-center gap-4 text-xs px-3 py-2.5 rounded-lg
                        bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700">
                <span class="text-neutral-500">Margin:</span>
                <span class="font-medium" :class="margin() > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-red-500'"
                      x-text="'Rp ' + margin().toLocaleString('id-ID')"></span>
            </div>
        </div>

        {{-- Keuangan: arah dana + biaya admin --}}
        <div class="card space-y-4" x-show="kind === 'keuangan'" x-cloak>
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Arah dana</h3>
            <div class="space-y-2">
                <template x-for="opt in [
                    {v:'none',  t:'Fee saja',      d:'Tidak menggerakkan saldo. Cuma catat fee (token, voucher, dll).'},
                    {v:'tarik', t:'Tarik tunai',   d:'Saldo bank turun, kas fisik naik. (pelanggan ambil tunai)'},
                    {v:'setor', t:'Setor / transfer', d:'Saldo bank naik, kas fisik turun. (kirim uang / setor)'}
                ]" :key="opt.v">
                    <label class="flex items-start gap-2.5 p-3 rounded-xl cursor-pointer border transition-colors duration-150"
                           :class="cash_direction === opt.v ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'border-neutral-200 dark:border-neutral-700'">
                        <input type="radio" name="cash_direction" :value="opt.v" x-model="cash_direction" class="mt-0.5 accent-primary-600">
                        <span>
                            <span class="block text-xs font-medium text-neutral-900 dark:text-neutral-100" x-text="opt.t"></span>
                            <span class="block text-[11px] text-neutral-500 mt-0.5" x-text="opt.d"></span>
                        </span>
                    </label>
                </template>
            </div>

            <div class="pt-2 border-t border-neutral-100 dark:border-neutral-800">
                <label class="label">Fee default (Rp)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-neutral-500 pointer-events-none">Rp</span>
                    <input type="text" :value="formatNum(default_fee)"
                           @input="default_fee = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                           class="input pl-9" placeholder="0">
                    <input type="hidden" name="default_fee" :value="default_fee">
                </div>
                <p class="text-[11px] text-neutral-400 mt-1">
                    Dipakai bila tarif bertingkat di bawah kosong.
                </p>
            </div>

            {{-- Tarif bertingkat per nominal --}}
            <div class="pt-2 border-t border-neutral-100 dark:border-neutral-800 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="label !mb-0">Biaya admin otomatis (per nominal)</label>
                    <button type="button" @click="addTier()"
                            class="inline-flex items-center gap-1 text-[11px] font-medium text-primary-600 dark:text-primary-400 hover:underline">
                        <x-heroicon-o-plus class="w-3 h-3" /> Tambah tingkat
                    </button>
                </div>
                <p class="text-[11px] text-neutral-400">
                    Saat kasir mengetik nominal, fee terisi otomatis. Kosongkan kolom "s/d nominal" untuk tingkat paling atas (tak terbatas).
                </p>

                <template x-for="(t, i) in tiers" :key="i">
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] text-neutral-400 pointer-events-none">s/d</span>
                                <input type="text" :name="'fee_tiers['+i+'][max]'"
                                       :value="t.max ? formatNum(t.max) : ''"
                                       @input="t.max = parseInt($event.target.value.replace(/\D/g,'')) || ''"
                                       class="input !py-1.5 text-xs pl-8" placeholder="nominal (kosong = ∞)">
                            </div>
                        </div>
                        <span class="text-neutral-400 text-xs">→ fee</span>
                        <div class="w-28">
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] text-neutral-400 pointer-events-none">Rp</span>
                                <input type="text" :name="'fee_tiers['+i+'][fee]'"
                                       :value="t.fee ? formatNum(t.fee) : ''"
                                       @input="t.fee = parseInt($event.target.value.replace(/\D/g,'')) || ''"
                                       class="input !py-1.5 text-xs pl-7" placeholder="0">
                            </div>
                        </div>
                        <button type="button" @click="removeTier(i)" class="text-neutral-400 hover:text-red-500">
                            <x-heroicon-o-trash class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Eceran: info harga diisi saat transaksi --}}
        <div class="card" x-show="kind === 'eceran'" x-cloak>
            <div class="flex items-start gap-3 text-xs text-neutral-600 dark:text-neutral-300">
                <x-heroicon-o-information-circle class="w-4 h-4 shrink-0 text-primary-500 mt-0.5" />
                <p>
                    Harga jual &amp; harga modal <strong>tidak disetel di sini</strong>.
                    Kasir mengetiknya tiap transaksi saat menekan <strong>Catat</strong>,
                    dan profit dihitung otomatis (harga jual − modal).
                </p>
            </div>
        </div>

        {{-- Rita: produk voucher terkait + saldo deposit --}}
        <div class="card space-y-4" x-show="kind === 'rita'" x-cloak>
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Pengaturan Rita</h3>

            <div>
                <label class="label">Produk voucher terkait <span class="text-red-500">*</span></label>
                <select name="product_id" class="select w-full @error('product_id') border-red-400 @enderror">
                    <option value="">-- Pilih produk voucher --</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') === $product->id ? 'selected' : '' }}>
                            {{ $product->name }} (stok {{ $product->stock }})
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-neutral-400 mt-1">Stok produk ini berkurang tiap nembak voucher.</p>
                @error('product_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label">Saldo Rita / deposit (Rp)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-neutral-500 pointer-events-none">Rp</span>
                    <input type="text" :value="formatNum(rita_balance)"
                           @input="rita_balance = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                           class="input pl-9" placeholder="0">
                    <input type="hidden" name="rita_balance" :value="rita_balance">
                </div>
                <p class="text-[11px] text-neutral-400 mt-1">
                    Saldo deposit berkurang sebesar modal tiap transaksi. Top-up saldo lewat Edit layanan.
                </p>
            </div>
        </div>

        {{-- Pengaturan --}}
        <div class="card space-y-3">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Pengaturan</h3>
            <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded accent-primary-600">
                <span class="text-xs text-neutral-700 dark:text-neutral-300">Jasa aktif</span>
            </label>
        </div>

        <button type="submit" class="btn-primary justify-center w-full">
            <x-heroicon-o-check class="w-4 h-4" />
            Simpan jasa
        </button>
    </div>
</form>

@endsection
