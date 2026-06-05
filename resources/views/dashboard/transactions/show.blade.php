@extends('layouts.app')
@section('title', 'Detail Transaksi')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('transactions.index') }}" class="btn-secondary !text-xs !py-1.5">
        <x-heroicon-o-arrow-left class="w-3.5 h-3.5" /> Kembali
    </a>
    <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $transaction->trx_number }}</h2>
    @if ($transaction->status === 'completed')
        <span class="badge-success">Selesai</span>
    @elseif ($transaction->status === 'void')
        <span class="badge-danger">Batal</span>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 card space-y-4">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr><th>Item</th><th class="text-right">Harga</th><th class="text-center">Qty</th><th class="text-right">Subtotal</th></tr>
                </thead>
                <tbody>
                    @foreach ($transaction->items as $item)
                        <tr>
                            <td class="text-xs font-medium text-neutral-900 dark:text-neutral-100">{{ $item->item_name }}</td>
                            <td class="text-right text-xs">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-center text-xs">{{ $item->qty }}</td>
                            <td class="text-right text-xs">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="space-y-1 text-xs border-t border-neutral-100 dark:border-neutral-800 pt-3">
            <div class="flex justify-between text-neutral-500"><span>Subtotal</span><span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span></div>
            @if ($transaction->discount_amount > 0)
                <div class="flex justify-between text-neutral-500">
                    <span>Diskon @if ($transaction->discount_type === 'percent')({{ rtrim(rtrim($transaction->discount_value, '0'), '.') }}%)@endif</span>
                    <span>− Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="flex justify-between text-sm font-semibold text-neutral-900 dark:text-neutral-100 pt-1"><span>Total</span><span>Rp {{ number_format($transaction->total, 0, ',', '.') }}</span></div>
            <div class="flex justify-between text-neutral-500 pt-2"><span>Tunai</span><span>Rp {{ number_format($transaction->paid_cash, 0, ',', '.') }}</span></div>
            <div class="flex justify-between text-neutral-500"><span>Transfer</span><span>Rp {{ number_format($transaction->paid_transfer, 0, ',', '.') }}</span></div>
            <div class="flex justify-between text-neutral-500"><span>Kembalian</span><span>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span></div>
        </div>

        @if ($transaction->note)
            <p class="text-xs text-neutral-500">Catatan: {{ $transaction->note }}</p>
        @endif
        @if ($transaction->status === 'void' && $transaction->void_reason)
            <p class="text-xs text-red-500">Alasan batal: {{ $transaction->void_reason }}</p>
        @endif
    </div>

    <div class="space-y-4">
        <div class="card space-y-2 text-xs">
            <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">Info</h3>
            <div class="flex justify-between text-neutral-500"><span>Kasir</span><span>{{ $transaction->kasir?->name ?? '-' }}</span></div>
            <div class="flex justify-between text-neutral-500"><span>Waktu</span><span>{{ $transaction->created_at->translatedFormat('d M Y H:i') }}</span></div>
            <div class="flex justify-between text-neutral-500"><span>Metode</span><span>{{ $transaction->payment_label }}</span></div>
        </div>
    </div>
</div>

@endsection
