@extends('layouts.app')
@section('title', 'Edit Produk')

@section('content')

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('products.show', $product) }}" class="btn-secondary !text-xs !py-1.5">
            <x-heroicon-o-arrow-left class="w-3.5 h-3.5" />
            Kembali
        </a>
        <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
            Edit Produk
        </h2>
    </div>

    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" x-data="{
        price: {{ $product->price }},
        price_wholesale: {{ $product->price_wholesale }},
        cost_price: {{ $product->cost_price }},
        imagePreview: '{{ $product->image_url ?? '' }}',
        onImage(e) {
            const f = e.target.files[0]
            if (f) this.imagePreview = URL.createObjectURL(f)
        },
        formatNum(val) {
            if (!val || val == 0) return ''
            return new Intl.NumberFormat('id-ID').format(val)
        },
        margin() {
            return this.price - this.cost_price
        }
    }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <div class="lg:col-span-2 space-y-4">

                <div class="card space-y-4">
                    <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                        Informasi produk
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="label">Nama produk <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}"
                                class="input @error('name') border-red-400 @enderror" required>
                            @error('name')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="label">SKU</label>
                            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="input">
                        </div>

                        <div>
                            <label class="label">Kategori</label>
                            <select name="category_id" class="select">
                                <option value="">-- Pilih kategori --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('category_id', $product->category_id) === $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="label">Catatan</label>
                        <textarea name="note" rows="2" class="input resize-none">{{ old('note', $product->note) }}</textarea>
                    </div>
                </div>

                <div class="card space-y-4">
                    <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Harga</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="label">Harga modal (Rp)</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                                <input type="text" :value="formatNum(cost_price)"
                                    @input="cost_price = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                    @keydown="if(['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight'].includes($event.key)) return; if(!/[0-9]/.test($event.key)) $event.preventDefault();"
                                    class="input pl-9">
                                <input type="hidden" name="cost_price" :value="cost_price">
                            </div>
                        </div>

                        <div>
                            <label class="label">Harga jual ecer (Rp)</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                                <input type="text" :value="formatNum(price)"
                                    @input="price = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                    @keydown="if(['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight'].includes($event.key)) return; if(!/[0-9]/.test($event.key)) $event.preventDefault();"
                                    class="input pl-9">
                                <input type="hidden" name="price" :value="price">
                            </div>
                        </div>

                        <div>
                            <label class="label">Harga grosir (Rp)</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2
                                         text-sm text-neutral-500 pointer-events-none">Rp</span>
                                <input type="text" :value="formatNum(price_wholesale)"
                                    @input="price_wholesale = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                                    @keydown="if(['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight'].includes($event.key)) return; if(!/[0-9]/.test($event.key)) $event.preventDefault();"
                                    class="input pl-9">
                                <input type="hidden" name="price_wholesale" :value="price_wholesale">
                            </div>
                        </div>
                    </div>

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

            <div class="space-y-4">

                {{-- Foto produk --}}
                <div class="card space-y-3">
                    <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Foto produk</h3>

                    <label class="block cursor-pointer">
                        <div class="aspect-square rounded-xl border-2 border-dashed
                                    border-neutral-200 dark:border-neutral-700
                                    flex items-center justify-center overflow-hidden
                                    hover:border-primary-400 dark:hover:border-primary-600 transition-colors">
                            <template x-if="imagePreview">
                                <img :src="imagePreview" class="w-full h-full object-cover" alt="Foto produk">
                            </template>
                            <template x-if="!imagePreview">
                                <div class="text-center px-4">
                                    <x-heroicon-o-photo class="w-8 h-8 mx-auto text-neutral-300 dark:text-neutral-600" />
                                    <p class="text-[11px] text-neutral-400 mt-2">Klik untuk pilih foto</p>
                                    <p class="text-[10px] text-neutral-400 mt-0.5">JPG/PNG/WEBP, maks 2 MB</p>
                                </div>
                            </template>
                        </div>
                        <input type="file" name="image" accept="image/*" @change="onImage($event)" class="hidden">
                    </label>

                    @error('image')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    <p class="text-[11px] text-amber-600 dark:text-amber-400 flex items-start gap-1">
                        <x-heroicon-o-information-circle class="w-3.5 h-3.5 shrink-0 mt-px" />
                        Khusus kategori <strong>aksesoris</strong>, foto produk wajib ada.
                    </p>
                </div>

                <div class="card space-y-4">
                    <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Stok</h3>

                    <div class="bg-neutral-50 dark:bg-neutral-800 rounded-xl p-3 text-xs">
                        <p class="text-neutral-500 mb-1">Stok saat ini</p>
                        <p
                            class="text-xl font-semibold
                               {{ $product->stock <= 0
                                   ? 'text-red-500'
                                   : ($product->is_low_stock
                                       ? 'text-amber-500'
                                       : 'text-neutral-900 dark:text-neutral-100') }}">
                            {{ $product->stock }}
                        </p>
                        <p class="text-neutral-400 mt-0.5">
                            Alert di ≤ {{ $product->stock_alert }}
                        </p>
                    </div>

                    <div>
                        <label class="label">Alert stok menipis</label>
                        <input type="number" name="stock_alert" value="{{ old('stock_alert', $product->stock_alert) }}"
                            min="0" class="input">
                    </div>

                    <p class="text-[11px] text-neutral-400">
                        Untuk ubah stok, gunakan fitur
                        <a href="{{ route('products.show', $product) }}"
                            class="text-primary-600 dark:text-primary-400 hover:underline">
                            Adjust Stok
                        </a>
                        di halaman detail.
                    </p>
                </div>

                <div class="card space-y-3">
                    <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Pengaturan</h3>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }}
                            class="w-4 h-4 rounded accent-primary-600">
                        <span class="text-xs text-neutral-700 dark:text-neutral-300">
                            Produk aktif (tampil di kasir)
                        </span>
                    </label>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <x-heroicon-o-check class="w-4 h-4" />
                        Simpan perubahan
                    </button>
                </div>

                {{-- Hapus produk --}}
                <div x-data="{ confirm: false }">
                    <button type="button" @click="confirm = true" class="btn-danger w-full justify-center !text-xs">
                        <x-heroicon-o-trash class="w-3.5 h-3.5" />
                        Hapus produk
                    </button>

                    <div x-cloak x-show="confirm"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                        <div
                            class="w-full max-w-sm bg-white dark:bg-neutral-900
                                border border-neutral-200 dark:border-neutral-800
                                rounded-2xl overflow-hidden">
                            <div class="px-5 py-4">
                                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">
                                    Hapus produk?
                                </p>
                                <p class="text-xs text-neutral-500">
                                    Produk <strong>{{ $product->name }}</strong> akan dihapus.
                                    Data transaksi yang sudah ada tidak terpengaruh.
                                </p>
                            </div>
                            <div class="flex gap-2 px-5 py-4 border-t border-neutral-200 dark:border-neutral-800">
                                <button type="button" @click="confirm = false"
                                    class="btn-secondary flex-1 justify-center !text-xs">
                                    Batal
                                </button>
                                <button type="submit" form="delete-product-form"
                                    class="btn-danger flex-1 justify-center !text-xs">
                                    Ya, hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>

    <form action="{{ route('products.destroy', $product) }}" id="delete-product-form" class="hidden">
        @csrf
        @method('DELETE')

    </form>

@endsection
