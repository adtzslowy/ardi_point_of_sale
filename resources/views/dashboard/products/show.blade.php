@extends('layouts.app')
@section('title', 'Detail Produk')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('products.index') }}" class="btn-secondary !text-xs !py-1.5">
        <x-heroicon-o-arrow-left class="w-3.5 h-3.5" />
        Kembali
    </a>
    <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
        {{ $product->name }}
    </h2>
    @if ($product->is_active)
        <span class="badge-primary">Aktif</span>
    @else
        <span class="badge-neutral">Nonaktif</span>
    @endif
    <a href="{{ route('products.edit', $product) }}" class="btn-secondary !text-xs !py-1.5 ml-auto">
        <x-heroicon-o-pencil class="w-3.5 h-3.5" />
        Edit
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Kiri: Info + adjust stok --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Info produk --}}
        <div class="card">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100 mb-4">
                Informasi produk
            </h3>
            <div class="grid grid-cols-2 gap-y-4 gap-x-8 text-sm">
                <div>
                    <p class="text-xs text-neutral-500 mb-1">Nama produk</p>
                    <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $product->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-neutral-500 mb-1">SKU</p>
                    <p class="font-medium text-neutral-900 dark:text-neutral-100">
                        {{ $product->sku ?? '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-neutral-500 mb-1">Kategori</p>
                    <p class="font-medium text-neutral-900 dark:text-neutral-100">
                        {{ $product->category?->name ?? '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-neutral-500 mb-1">Alert stok</p>
                    <p class="font-medium text-neutral-900 dark:text-neutral-100">
                        ≤ {{ $product->stock_alert }}
                    </p>
                </div>
                @if ($product->note)
                    <div class="col-span-2">
                        <p class="text-xs text-neutral-500 mb-1">Catatan</p>
                        <p class="font-medium text-neutral-900 dark:text-neutral-100">
                            {{ $product->note }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Adjust stok --}}
        <div class="card" x-data="{
            type: 'in',
            qty: '',
            note: '',
            get label() {
                if (this.type === 'in')          return 'Tambah stok masuk'
                if (this.type === 'adjustment')  return 'Penyesuaian stok'
                if (this.type === 'owner_take')  return 'Owner ambil stok'
                return ''
            }
        }">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100 mb-4">
                Adjust stok
            </h3>

            <form method="POST" action="{{ route('products.adjust-stock', $product) }}"
                  class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="label">Tipe</label>
                        <select name="type" x-model="type" class="select">
                            <option value="in">Stok masuk</option>
                            <option value="adjustment">Penyesuaian</option>
                            @can('owner take stock')
                                <option value="owner_take">Ambil (owner)</option>
                            @endcan
                        </select>
                    </div>
                    <div>
                        <label class="label">Jumlah</label>
                        <input type="number" name="qty" x-model="qty"
                               min="1" required class="input" placeholder="0">
                    </div>
                    <div>
                        <label class="label">Stok sesudah</label>
                        <div class="input bg-neutral-50 dark:bg-neutral-800 font-medium"
                             :class="{
                                 'text-primary-600 dark:text-primary-400': type === 'in',
                                 'text-red-500': type !== 'in' && ({{ $product->stock }} - (parseInt(qty)||0)) < 0,
                                 'text-neutral-900 dark:text-neutral-100': type !== 'in' && ({{ $product->stock }} - (parseInt(qty)||0)) >= 0,
                             }"
                             x-text="type === 'in'
                                 ? {{ $product->stock }} + (parseInt(qty)||0)
                                 : {{ $product->stock }} - (parseInt(qty)||0)"
                        ></div>
                    </div>
                </div>

                <div>
                    <label class="label">Keterangan <span class="text-red-500">*</span></label>
                    <input type="text" name="note" x-model="note" required
                           class="input" :placeholder="label + ' - tulis keterangan'">
                </div>

                <button type="submit" class="btn-primary !text-xs">
                    <x-heroicon-o-check class="w-3.5 h-3.5" />
                    Simpan perubahan stok
                </button>
            </form>
        </div>

        {{-- Riwayat stok --}}
        <div class="card p-0 overflow-hidden">
            <div class="px-4 py-3 border-b border-neutral-200 dark:border-neutral-800">
                <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                    Riwayat pergerakan stok
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Tipe</th>
                            <th>Oleh</th>
                            <th class="text-center">Sebelum</th>
                            <th class="text-center">Perubahan</th>
                            <th class="text-center">Sesudah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($product->stockMovements as $mov)
                            <tr>
                                <td class="text-xs whitespace-nowrap">
                                    {{ $mov->created_at->translatedFormat('d M H:i') }}
                                </td>
                                <td>
                                    <span @class([
                                        'badge-success' => $mov->type === 'in',
                                        'badge-danger'  => $mov->type === 'out',
                                        'badge-warning' => $mov->type === 'adjustment',
                                        'badge-neutral' => $mov->type === 'owner_take',
                                    ])>{{ $mov->type_label }}</span>
                                </td>
                                <td class="text-xs">{{ $mov->user->name }}</td>
                                <td class="text-center text-xs">{{ $mov->qty_before }}</td>
                                <td class="text-center text-xs font-medium"
                                    :class="">
                                    <span @class([
                                        'text-primary-600 dark:text-primary-400' => $mov->qty_change > 0,
                                        'text-red-500' => $mov->qty_change < 0,
                                    ])>
                                        {{ $mov->qty_change > 0 ? '+' : '' }}{{ $mov->qty_change }}
                                    </span>
                                </td>
                                <td class="text-center text-xs font-medium
                                           text-neutral-900 dark:text-neutral-100">
                                    {{ $mov->qty_after }}
                                </td>
                                <td class="text-xs text-neutral-500">
                                    {{ $mov->note ?? $mov->reference ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-xs text-neutral-400">
                                    Belum ada riwayat stok
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Kanan: Stok & harga --}}
    <div class="space-y-4">

        {{-- Stok --}}
        <div class="card">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100 mb-4">Stok</h3>
            <div class="text-center py-4">
                <p class="text-5xl font-bold mb-2
                           {{ $product->stock <= 0
                               ? 'text-red-500'
                               : ($product->is_low_stock ? 'text-amber-500' : 'text-primary-600 dark:text-primary-400') }}">
                    {{ $product->stock }}
                </p>
                @if ($product->stock <= 0)
                    <span class="badge-danger">Stok habis</span>
                @elseif ($product->is_low_stock)
                    <span class="badge-warning">Stok menipis</span>
                @else
                    <span class="badge-success">Stok aman</span>
                @endif
                <p class="text-xs text-neutral-400 mt-2">Alert di ≤ {{ $product->stock_alert }}</p>
            </div>
        </div>

        {{-- Harga --}}
        <div class="card space-y-3">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Harga</h3>
            <div class="space-y-2.5">
                <div class="flex justify-between text-xs">
                    <span class="text-neutral-500">Modal</span>
                    <span class="font-medium text-neutral-900 dark:text-neutral-100">
                        Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-neutral-500">Jual ecer</span>
                    <span class="font-medium text-neutral-900 dark:text-neutral-100">
                        Rp {{ number_format($product->price, 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-neutral-500">Jual grosir</span>
                    <span class="font-medium text-neutral-900 dark:text-neutral-100">
                        Rp {{ number_format($product->price_wholesale, 0, ',', '.') }}
                    </span>
                </div>
                <div class="h-px bg-neutral-100 dark:bg-neutral-800"></div>
                <div class="flex justify-between text-xs font-medium">
                    <span class="text-neutral-500">Margin ecer</span>
                    <span class="text-primary-600 dark:text-primary-400">
                        Rp {{ number_format($product->profit_margin, 0, ',', '.') }}
                        ({{ $product->price > 0 ? round($product->profit_margin / $product->price * 100) : 0 }}%)
                    </span>
                </div>
                @if (auth()->user()->hasRole('owner') || auth()->user()->hasRole('admin'))
                    <div class="flex justify-between text-xs font-medium">
                        <span class="text-neutral-500">Nilai stok</span>
                        <span class="text-neutral-900 dark:text-neutral-100">
                            Rp {{ number_format($product->stock * $product->cost_price, 0, ',', '.') }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>

@endsection
