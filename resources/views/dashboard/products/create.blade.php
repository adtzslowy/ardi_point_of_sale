@extends('layouts.app')
@section('title', 'Tambah Produk')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('products.index') }}" class="btn-secondary !text-xs !py-1.5">
        <x-heroicon-o-arrow-left class="w-3.5 h-3.5" />
        Kembali
    </a>
    <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Tambah Produk</h2>
</div>

<form method="POST" action="{{ route('products.store') }}"
      x-data="{
          price:           0,
          price_wholesale: 0,
          cost_price:      0,
          stock:           0,
          formatNum(val) {
              if (!val || val == 0) return ''
              return new Intl.NumberFormat('id-ID').format(val)
          },
          margin() {
              return this.price - this.cost_price
          }
      }">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Kiri: Info produk --}}
        <div class="lg:col-span-2 space-y-4">

            <div class="card space-y-4">
                <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                    Informasi produk
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="label">Nama produk <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="input @error('name') border-red-400 @enderror"
                               placeholder="mis: Samsung Galaxy A15 5G" required>
                        @error('name')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="label">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku') }}"
                               class="input" placeholder="mis: HP-001">
                    </div>

                    <div x-data="{
                            open: false,
                            name: '',
                            error: '',
                            loading: false,
                            openModal() {
                                this.open  = true
                                this.error = ''
                                this.name  = ''
                                this.$nextTick(() => this.$refs.nameInput?.focus())
                            },
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
                                        body: JSON.stringify({ name: this.name.trim() })
                                    })
                                    const data = await res.json()
                                    if (!res.ok) {
                                        this.error = (data.errors && data.errors.name)
                                            ? data.errors.name[0]
                                            : (data.message || 'Gagal menyimpan kategori.')
                                        return
                                    }
                                    window.dispatchEvent(new CustomEvent('category-added', { detail: data }))
                                    this.name = ''
                                    this.open = false
                                } catch (e) {
                                    this.error = 'Terjadi kesalahan jaringan.'
                                } finally {
                                    this.loading = false
                                }
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

                        {{-- Modal tambah kategori --}}
                        <div x-cloak x-show="open"
                             @keydown.escape.window="open = false"
                             x-transition.opacity
                             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                            <div @click.outside="open = false"
                                 class="w-full max-w-sm bg-white dark:bg-neutral-900
                                        border border-neutral-200 dark:border-neutral-800
                                        rounded-2xl overflow-hidden">
                                <div class="px-5 py-4">
                                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">
                                        Tambah kategori produk
                                    </p>
                                    <p class="text-xs text-neutral-500 mb-3">
                                        Kategori dibuat untuk cabang yang sedang aktif.
                                    </p>
                                    <input type="text"
                                           x-model="name"
                                           x-ref="nameInput"
                                           @keydown.enter.prevent="submit()"
                                           maxlength="255"
                                           placeholder="mis: Charger & Kabel"
                                           class="input"
                                           :class="error ? 'border-red-400' : ''">
                                    <p x-show="error" x-cloak x-text="error"
                                       class="text-xs text-red-500 mt-1"></p>
                                </div>
                                <div class="flex gap-2 px-5 py-4 border-t border-neutral-200 dark:border-neutral-800">
                                    <button type="button" @click="open = false"
                                            class="btn-secondary flex-1 justify-center !text-xs">
                                        Batal
                                    </button>
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
                </div>

                <div>
                    <label class="label">Catatan</label>
                    <textarea name="note" rows="2" class="input resize-none"
                              placeholder="Catatan tambahan...">{{ old('note') }}</textarea>
                </div>
            </div>

            {{-- Harga --}}
            <div class="card space-y-4">
                <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                    Harga
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label">Harga modal (Rp) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                            <input
                                type="text"
                                :value="formatNum(cost_price)"
                                @input="cost_price = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                @keydown="if(['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight'].includes($event.key)) return; if(!/[0-9]/.test($event.key)) $event.preventDefault();"
                                class="input pl-9 @error('cost_price') border-red-400 @enderror"
                                placeholder="0"
                            >
                            <input type="hidden" name="cost_price" :value="cost_price">
                        </div>
                        @error('cost_price')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="label">Harga jual ecer (Rp) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                            <input
                                type="text"
                                :value="formatNum(price)"
                                @input="price = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                @keydown="if(['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight'].includes($event.key)) return; if(!/[0-9]/.test($event.key)) $event.preventDefault();"
                                class="input pl-9 @error('price') border-red-400 @enderror"
                                placeholder="0"
                            >
                            <input type="hidden" name="price" :value="price">
                        </div>
                        @error('price')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="label">Harga grosir (Rp) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                            <input
                                type="text"
                                :value="formatNum(price_wholesale)"
                                @input="price_wholesale = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                @keydown="if(['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight'].includes($event.key)) return; if(!/[0-9]/.test($event.key)) $event.preventDefault();"
                                class="input pl-9 @error('price_wholesale') border-red-400 @enderror"
                                placeholder="0"
                            >
                            <input type="hidden" name="price_wholesale" :value="price_wholesale">
                        </div>
                    </div>
                </div>

                {{-- Preview margin --}}
                <div x-show="cost_price > 0 && price > 0"
                     class="flex items-center gap-4 text-xs px-3 py-2.5 rounded-lg
                            bg-neutral-50 dark:bg-neutral-800
                            border border-neutral-200 dark:border-neutral-700">
                    <span class="text-neutral-500">Margin ecer:</span>
                    <span class="font-medium"
                          :class="margin() > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-red-500'"
                          x-text="'Rp ' + margin().toLocaleString('id-ID') + ' (' + (price > 0 ? Math.round(margin()/price*100) : 0) + '%)'">
                    </span>
                </div>
            </div>

        </div>

        {{-- Kanan: Stok --}}
        <div class="space-y-4">

            <div class="card space-y-4">
                <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                    Stok awal
                </h3>

                <div>
                    <label class="label">Jumlah stok <span class="text-red-500">*</span></label>
                    <input
                        type="number"
                        name="stock"
                        value="{{ old('stock', 0) }}"
                        x-model="stock"
                        min="0"
                        class="input @error('stock') border-red-400 @enderror"
                    >
                    @error('stock')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="label">Alert stok menipis <span class="text-red-500">*</span></label>
                    <input
                        type="number"
                        name="stock_alert"
                        value="{{ old('stock_alert', 5) }}"
                        min="0"
                        class="input @error('stock_alert') border-red-400 @enderror"
                    >
                    <p class="text-[11px] text-neutral-400 mt-1">
                        Notifikasi muncul saat stok ≤ angka ini
                    </p>
                </div>

                {{-- Preview nilai stok --}}
                <div x-show="stock > 0 && cost_price > 0"
                     class="text-xs px-3 py-2.5 rounded-lg
                            bg-primary-50 dark:bg-primary-900/20
                            border border-primary-200 dark:border-primary-800
                            text-primary-700 dark:text-primary-300">
                    Nilai stok:
                    <span class="font-medium"
                          x-text="'Rp ' + (stock * cost_price).toLocaleString('id-ID')">
                    </span>
                </div>
            </div>

            <div class="card space-y-3">
                <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                    Pengaturan
                </h3>
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           checked class="w-4 h-4 rounded accent-primary-600">
                    <span class="text-xs text-neutral-700 dark:text-neutral-300">
                        Produk aktif (tampil di kasir)
                    </span>
                </label>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <x-heroicon-o-check class="w-4 h-4" />
                    Simpan produk
                </button>
            </div>

        </div>
    </div>
</form>

@endsection
