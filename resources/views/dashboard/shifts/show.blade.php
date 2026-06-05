@extends('layouts.app')
@section('title', 'Detail Shift')

@section('content')

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('shifts.index') }}" class="btn-secondary !text-xs !py-1.5">
            <x-heroicon-o-arrow-left class="w-3.5 h-3.5" />
            Kembali
        </a>
        <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
            Detail Shift {{ $shift->type_label }}
        </h2>
        @if ($shift->status === 'open')
            <span class="badge-primary">Aktif</span>
        @else
            <span class="badge-neutral">Tutup</span>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Info shift --}}
        <div class="card lg:col-span-2">
            <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100 mb-6">
                Informasi shift
            </h3>
            <div class="grid grid-cols-2 gap-y-6 gap-x-8">
                <div>
                    <p class="text-sm text-neutral-500 mb-1.5">Tipe shift</p>
                    <p class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                        Shift {{ $shift->type_label }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-neutral-500 mb-1.5">Status</p>
                    <div class="mt-1">
                        @if ($shift->status === 'open')
                            <span class="badge-primary text-sm px-3 py-1">Aktif</span>
                        @else
                            <span class="badge-neutral text-sm px-3 py-1">Tutup</span>
                        @endif
                    </div>
                </div>
                <div>
                    <p class="text-sm text-neutral-500 mb-1.5">Dibuka oleh</p>
                    <p class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                        {{ $shift->opener->name }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-neutral-500 mb-1.5">Waktu buka</p>
                    <p class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                        {{ $shift->opened_at->translatedFormat('d F Y, H:i') }}
                    </p>
                </div>
                @if ($shift->closer)
                    <div>
                        <p class="text-sm text-neutral-500 mb-1.5">Ditutup oleh</p>
                        <p class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                            {{ $shift->closer->name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-neutral-500 mb-1.5">Waktu tutup</p>
                        <p class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                            {{ $shift->closed_at->translatedFormat('d F Y, H:i') }}
                        </p>
                    </div>
                @endif
                <div>
                    <p class="text-sm text-neutral-500 mb-1.5">Durasi</p>
                    <p class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                        {{ $shift->duration }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-neutral-500 mb-1.5">Cabang</p>
                    <p class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                        {{ $shift->branch->name }}
                    </p>
                </div>
                @if ($shift->note)
                    <div class="col-span-2">
                        <p class="text-sm text-neutral-500 mb-1.5">Catatan</p>
                        <p class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                            {{ $shift->note }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Ringkasan kas --}}
        <div class="space-y-3">

            <div class="card">
                <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100 mb-3">
                    Ringkasan kas
                </h3>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-neutral-500">Modal awal</span>
                        <span class="font-medium text-neutral-900 dark:text-neutral-100">
                            Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-neutral-500">Penjualan tunai</span>
                        <span class="font-medium text-primary-600 dark:text-primary-400">
                            + Rp {{ number_format($shift->total_cash, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-neutral-500">Penjualan transfer</span>
                        <span class="font-medium text-blue-600 dark:text-blue-400">
                            Rp {{ number_format($shift->total_transfer, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="h-px bg-neutral-100 dark:bg-neutral-800"></div>
                    <div class="flex justify-between text-xs font-medium">
                        <span class="text-neutral-700 dark:text-neutral-300">Total kas fisik</span>
                        <span class="text-neutral-900 dark:text-neutral-100">
                            Rp {{ number_format($shift->opening_cash + $shift->total_cash, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex justify-between text-xs font-medium">
                        <span class="text-neutral-700 dark:text-neutral-300">Total penjualan</span>
                        <span class="text-neutral-900 dark:text-neutral-100">
                            Rp {{ number_format($shift->total_sales, 0, ',', '.') }}
                        </span>
                    </div>

                    @if ($shift->closing_cash !== null)
                        <div class="h-px bg-neutral-100 dark:bg-neutral-800"></div>
                        <div class="flex justify-between text-xs">
                            <span class="text-neutral-500">Uang fisik saat tutup</span>
                            <span class="font-medium text-neutral-900 dark:text-neutral-100">
                                Rp {{ number_format($shift->closing_cash, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between text-xs font-medium">
                            <span class="text-neutral-700 dark:text-neutral-300">Selisih</span>
                            <span @class([
                                'text-primary-600 dark:text-primary-400' => $shift->difference == 0,
                                'text-blue-600 dark:text-blue-400' => $shift->difference > 0,
                                'text-red-500' => $shift->difference < 0,
                            ])>
                                @if ($shift->difference == 0)
                                    Pas ✓
                                @elseif ($shift->difference > 0)
                                    Lebih Rp {{ number_format($shift->difference, 0, ',', '.') }}
                                @else
                                    Kurang Rp {{ number_format(abs($shift->difference), 0, ',', '.') }}
                                @endif
                            </span>
                        </div>
                    @endif

                    @if (auth()->user()->hasRole('owner') || auth()->user()->hasRole('admin'))
                        <div class="h-px bg-neutral-100 dark:bg-neutral-800"></div>
                        <div
                            class="flex justify-between text-xs font-medium
                                text-primary-600 dark:text-primary-400">
                            <span>Laba bersih</span>
                            <span>Rp {{ number_format($shift->total_profit, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Summary transaksi --}}
            <div class="card">
                <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100 mb-3">
                    Ringkasan transaksi
                </h3>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-neutral-500">Total transaksi</span>
                        <span class="font-medium text-neutral-900 dark:text-neutral-100">
                            {{ $shift->total_transactions }}
                        </span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-neutral-500">Selesai</span>
                        <span class="font-medium text-primary-600 dark:text-primary-400">
                            {{ $shift->transactions->where('status', 'completed')->count() }}
                        </span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-neutral-500">Void</span>
                        <span class="font-medium text-red-500">
                            {{ $shift->transactions->where('status', 'void')->count() }}
                        </span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-neutral-500">Retur</span>
                        <span class="font-medium text-amber-500">
                            {{ $shift->transactions->where('status', 'return')->count() }}
                        </span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    {{-- Transaksi dalam shift --}}
    <div class="card p-0 overflow-hidden">
        <div
            class="flex items-center justify-between px-4 py-3
                border-b border-neutral-200 dark:border-neutral-800">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                Transaksi dalam shift ini
            </h3>
            <span class="badge-neutral">
                {{ $shift->transactions->count() }} transaksi
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Kasir</th>
                        <th>Waktu</th>
                        <th>Item</th>
                        <th>Bayar</th>
                        @if (auth()->user()->hasRole('owner') || auth()->user()->hasRole('admin'))
                            <th class="text-right">Laba</th>
                        @endif
                        <th class="text-right">Total</th>
                        <th class="text-right">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shift->transactions as $trx)
                        <tr>
                            <td>
                                <a href="{{ route('transactions.show', $trx) }}"
                                    class="text-xs font-medium text-primary-600
                                      dark:text-primary-400 hover:underline">
                                    {{ $trx->trx_number }}
                                </a>
                            </td>
                            <td class="text-xs">{{ $trx->kasir->name }}</td>
                            <td class="text-xs text-neutral-400 whitespace-nowrap">
                                {{ $trx->created_at->format('H:i') }}
                            </td>
                            <td class="text-xs">
                                {{ $trx->items->count() }} item
                            </td>
                            <td>
                                <span @class([
                                    'badge-neutral' => $trx->payment_method === 'cash',
                                    'badge-info' => $trx->payment_method === 'transfer',
                                    'badge-warning' => $trx->payment_method === 'mixed',
                                ])>{{ $trx->payment_label }}</span>
                            </td>
                            @if (auth()->user()->hasRole('owner') || auth()->user()->hasRole('admin'))
                                <td class="text-right text-xs text-primary-600 dark:text-primary-400">
                                    Rp {{ number_format($trx->total_profit, 0, ',', '.') }}
                                </td>
                            @endif
                            <td class="text-right text-xs font-medium">
                                Rp {{ number_format($trx->total, 0, ',', '.') }}
                            </td>
                            <td class="text-right">
                                <span @class([
                                    'badge-success' => $trx->status === 'completed',
                                    'badge-danger' => $trx->status === 'void',
                                    'badge-warning' => $trx->status === 'return',
                                ])>{{ $trx->status_label }}</span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('transactions.show', $trx) }}"
                                    class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-xs text-neutral-400">
                                Belum ada transaksi di shift ini
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($shift->transactions->count() > 0)
            <div
                class="px-4 py-3 border-t border-neutral-200 dark:border-neutral-800
                    flex items-center justify-between">
                <span class="text-xs text-neutral-500">
                    Total {{ $shift->transactions->count() }} transaksi
                </span>
                <span class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                    Rp {{ number_format($shift->transactions->sum('total'), 0, ',', '.') }}
                </span>
            </div>
        @endif
    </div>

@endsection
