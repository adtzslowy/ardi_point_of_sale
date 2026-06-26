{{--
    Kartu satu layanan. Klik kartu = buka modal "Catat" (lanjut ke transaksi).
    Ikon pensil = edit layanan. Butuh $service & $activeShift dari parent.
--}}
<div class="relative group">
    <button type="button"
            @if ($activeShift)
                @click="openSell({ id: '{{ $service->id }}', name: {{ Js::from($service->name) }}, kind: '{{ $service->kind }}', price: {{ (int) $service->price }}, default_fee: {{ (int) $service->default_fee }}, cash_direction: '{{ $service->cash_direction ?? 'none' }}', fee_tiers: {{ Js::from($service->fee_tiers ?? []) }}, rita_balance: {{ (int) ($service->rita_balance ?? 0) }}, product_stock: {{ (int) ($service->product?->stock ?? 0) }}, product_name: {{ Js::from($service->product?->name ?? '') }} })"
            @else
                disabled
            @endif
            class="w-full text-left px-3 py-2.5 rounded-xl border
                   border-neutral-200 dark:border-neutral-700
                   bg-white dark:bg-neutral-900
                   enabled:hover:border-primary-400 enabled:hover:bg-primary-50
                   dark:enabled:hover:bg-primary-900/20
                   transition-colors duration-150
                   disabled:opacity-60 disabled:cursor-not-allowed">
        <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100 truncate pr-5">
            {{ $service->name }}
        </p>
        <p class="text-[11px] mt-0.5
                  {{ $service->kind === 'keuangan' ? 'text-primary-600 dark:text-primary-400' : 'text-neutral-500' }}">
            @if ($service->kind === 'keuangan')
                Fee Rp {{ number_format($service->default_fee, 0, ',', '.') }}
            @elseif ($service->kind === 'eceran')
                Harga diisi saat transaksi
            @elseif ($service->kind === 'rita')
                Saldo Rp {{ number_format($service->rita_balance, 0, ',', '.') }} · stok {{ (int) ($service->product?->stock ?? 0) }} pcs
            @else
                Rp {{ number_format($service->price, 0, ',', '.') }}
            @endif
        </p>
    </button>

    <a href="{{ route('services.edit', $service) }}"
       title="Edit layanan"
       class="absolute top-1.5 right-1.5 p-1 rounded-md
              text-neutral-300 hover:text-neutral-600
              dark:text-neutral-600 dark:hover:text-neutral-300
              opacity-0 group-hover:opacity-100 transition-opacity">
        <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
    </a>
</div>
