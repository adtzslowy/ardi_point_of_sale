@extends('layouts.app')
@section('title', 'Edit Jasa')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('services.index') }}" class="btn-secondary !text-xs !py-1.5">
        <x-heroicon-o-arrow-left class="w-3.5 h-3.5" />
        Kembali
    </a>
    <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Edit Jasa</h2>
</div>

<form method="POST" action="{{ route('services.update', $service) }}"
      x-data="{
          kind:       '{{ old('kind', $service->kind) }}',
          price:       {{ (int) old('price', $service->price) }},
          cost_price:  {{ (int) old('cost_price', $service->cost_price) }},
          default_fee: {{ (int) old('default_fee', $service->default_fee) }},
          formatNum(val) {
              if (!val || val == 0) return ''
              return new Intl.NumberFormat('id-ID').format(val)
          },
          margin() { return this.price - this.cost_price }
      }">
    @csrf
    @method('PUT')

    <div class="max-w-2xl space-y-4">

        <div class="card space-y-3">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Jenis jasa</h3>
            <div class="grid grid-cols-2 gap-3">
                <label class="flex items-start gap-2.5 p-3 rounded-xl cursor-pointer border transition-colors duration-150"
                       :class="kind === 'servis' ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'border-neutral-200 dark:border-neutral-700'">
                    <input type="radio" name="kind" value="servis" x-model="kind" class="mt-0.5 accent-primary-600">
                    <span>
                        <span class="block text-xs font-medium text-neutral-900 dark:text-neutral-100">Jasa servis</span>
                        <span class="block text-[11px] text-neutral-500 mt-0.5">Harga tetap</span>
                    </span>
                </label>
                <label class="flex items-start gap-2.5 p-3 rounded-xl cursor-pointer border transition-colors duration-150"
                       :class="kind === 'keuangan' ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'border-neutral-200 dark:border-neutral-700'">
                    <input type="radio" name="kind" value="keuangan" x-model="kind" class="mt-0.5 accent-primary-600">
                    <span>
                        <span class="block text-xs font-medium text-neutral-900 dark:text-neutral-100">Jasa keuangan</span>
                        <span class="block text-[11px] text-neutral-500 mt-0.5">Nominal + fee</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="card space-y-4">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Informasi jasa</h3>

            <div>
                <label class="label">Nama jasa <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $service->name) }}"
                       class="input @error('name') border-red-400 @enderror" required>
                @error('name')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label">Kategori</label>
                <x-category-select :categories="$categories" :selected="$service->category"/>
            </div>

            <div>
                <label class="label">Catatan</label>
                <textarea name="note" rows="2" class="input resize-none">{{ old('note', $service->note) }}</textarea>
            </div>
        </div>

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

        <div class="card space-y-4" x-show="kind === 'keuangan'">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Biaya admin</h3>
            <div>
                <label class="label">Fee default (Rp)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-neutral-500 pointer-events-none">Rp</span>
                    <input type="text" :value="formatNum(default_fee)"
                           @input="default_fee = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                           class="input pl-9" placeholder="0">
                    <input type="hidden" name="default_fee" :value="default_fee">
                </div>
                <p class="text-[11px] text-neutral-400 mt-1">Bisa diubah lagi saat mencatat transaksi.</p>
            </div>
        </div>

        <div class="card space-y-3">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Pengaturan</h3>
            <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $service->is_active) ? 'checked' : '' }}
                       class="w-4 h-4 rounded accent-primary-600">
                <span class="text-xs text-neutral-700 dark:text-neutral-300">Jasa aktif</span>
            </label>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn-primary justify-center">
                <x-heroicon-o-check class="w-4 h-4" />
                Simpan perubahan
            </button>
        </div>

        {{-- Hapus jasa (form di luar form edit, dihubungkan via atribut form=) --}}
        <div x-data="{ confirm: false }">
            <button type="button" @click="confirm = true"
                    class="btn-danger justify-center !text-xs">
                <x-heroicon-o-trash class="w-3.5 h-3.5" />
                Hapus jasa
            </button>

            <div x-cloak x-show="confirm"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                <div class="w-full max-w-sm bg-white dark:bg-neutral-900
                            border border-neutral-200 dark:border-neutral-800 rounded-2xl overflow-hidden">
                    <div class="px-5 py-4">
                        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">Hapus jasa?</p>
                        <p class="text-xs text-neutral-500">
                            Jasa <strong>{{ $service->name }}</strong> akan dihapus.
                            Data transaksi yang sudah ada tidak terpengaruh.
                        </p>
                    </div>
                    <div class="flex gap-2 px-5 py-4 border-t border-neutral-200 dark:border-neutral-800">
                        <button type="button" @click="confirm = false"
                                class="btn-secondary flex-1 justify-center !text-xs">Batal</button>
                        <button type="submit" form="delete-service-form"
                                class="btn-danger flex-1 justify-center !text-xs">Ya, hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Form hapus terpisah (tidak nested di dalam form edit) --}}
<form id="delete-service-form" method="POST" action="{{ route('services.destroy', $service) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>

@endsection
