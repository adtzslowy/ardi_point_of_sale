@extends('layouts.app')
@section('title', 'Produk & Stok')

@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Produk & Stok</h2>
        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
            Kelola produk dan stok cabang
        </p>
    </div>
    <a href="{{ route('products.create') }}" class="btn-primary">
        <x-heroicon-o-plus class="w-4 h-4" />
        Tambah produk
    </a>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <div class="stat-card">
        <div class="flex items-center justify-between mb-1">
            <span class="stat-label">Total produk</span>
            <div class="w-6 h-6 rounded-lg bg-primary-50 dark:bg-primary-900/30
                        flex items-center justify-center">
                <x-heroicon-o-cube class="w-3 h-3 text-primary-600 dark:text-primary-400" />
            </div>
        </div>
        <p class="stat-value">{{ $products->total() }}</p>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between mb-1">
            <span class="stat-label">Stok menipis</span>
            <div class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-900/20
                        flex items-center justify-center">
                <x-heroicon-o-exclamation-triangle class="w-3 h-3 text-amber-500" />
            </div>
        </div>
        <p class="stat-value">{{ $lowStockCount }}</p>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between mb-1">
            <span class="stat-label">Stok habis</span>
            <div class="w-6 h-6 rounded-lg bg-red-50 dark:bg-red-900/20
                        flex items-center justify-center">
                <x-heroicon-o-x-circle class="w-3 h-3 text-red-500" />
            </div>
        </div>
        <p class="stat-value">{{ $emptyCount }}</p>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between mb-1">
            <span class="stat-label">Nilai aset stok</span>
            <div class="w-6 h-6 rounded-lg bg-primary-50 dark:bg-primary-900/30
                        flex items-center justify-center">
                <x-heroicon-o-banknotes class="w-3 h-3 text-primary-600 dark:text-primary-400" />
            </div>
        </div>
        <p class="stat-value text-base">Rp {{ number_format($totalValue, 0, ',', '.') }}</p>
    </div>
</div>

{{-- Filter & tabel --}}
<div class="card p-0 overflow-hidden">

    {{-- Filter --}}
    <form method="GET" action="{{ route('products.index') }}"
          class="flex flex-wrap items-center gap-2 px-4 py-3
                 border-b border-neutral-200 dark:border-neutral-800">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Cari nama, SKU..."
            class="input !w-44 !py-1.5 text-xs"
        >
        <select name="category" class="select !w-40 !py-1.5 text-xs">
            <option value="">Semua kategori</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') === $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        <select name="filter" class="select !w-36 !py-1.5 text-xs">
            <option value="">Semua stok</option>
            <option value="low_stock" {{ request('filter') === 'low_stock' ? 'selected' : '' }}>
                Stok menipis
            </option>
            <option value="empty" {{ request('filter') === 'empty' ? 'selected' : '' }}>
                Stok habis
            </option>
        </select>
        <select name="status" class="select !w-32 !py-1.5 text-xs">
            <option value="active"   {{ request('status', 'active') === 'active'   ? 'selected' : '' }}>Aktif</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
        </select>
        <button type="submit" class="btn-primary !py-1.5 !text-xs">Filter</button>
        @if (request()->hasAny(['search', 'category', 'filter', 'status']))
            <a href="{{ route('products.index') }}" class="btn-secondary !py-1.5 !text-xs">Reset</a>
        @endif
    </form>

    {{-- Tabel --}}
    <div class="overflow-x-auto">
        <table class="table-base">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th class="text-right">Harga jual</th>
                    <th class="text-right">Harga grosir</th>
                    <th class="text-right">Modal</th>
                    <th class="text-center">Stok</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                                {{ $product->name }}
                            </p>
                            @if ($product->sku)
                                <p class="text-[10px] text-neutral-400 mt-0.5">{{ $product->sku }}</p>
                            @endif
                        </td>
                        <td class="text-xs text-neutral-500">
                            {{ $product->category?->name ?? '-' }}
                        </td>
                        <td class="text-right text-xs">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </td>
                        <td class="text-right text-xs">
                            Rp {{ number_format($product->price_wholesale, 0, ',', '.') }}
                        </td>
                        <td class="text-right text-xs text-neutral-500">
                            Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @if ($product->stock <= 0)
                                <span class="badge-danger">Habis</span>
                            @elseif ($product->is_low_stock)
                                <span class="badge-warning">{{ $product->stock }}</span>
                            @else
                                <span class="badge-success">{{ $product->stock }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($product->is_active)
                                <span class="badge-primary">Aktif</span>
                            @else
                                <span class="badge-neutral">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('products.show', $product) }}"
                                   class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                    Detail
                                </a>
                                <a href="{{ route('products.edit', $product) }}"
                                   class="text-xs text-neutral-500 hover:text-neutral-700
                                          dark:text-neutral-400 dark:hover:text-neutral-200">
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center text-xs text-neutral-400">
                            Belum ada produk
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->hasPages())
        <div class="px-4 py-3 border-t border-neutral-200 dark:border-neutral-800">
            {{ $products->links() }}
        </div>
    @endif

</div>

@endsection
