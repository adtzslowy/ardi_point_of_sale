@extends('layouts.app')
@section('title', 'Kas & Shift')

@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Kas & Shift</h2>
        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
            {{ now()->translatedFormat('l, d F Y') }}
        </p>
    </div>
</div>

@if ($activeShift)

    {{-- Shift aktif --}}
    <div class="card mb-5 border-primary-200 dark:border-primary-800
                bg-primary-50/50 dark:bg-primary-900/10">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/50
                            flex items-center justify-center shrink-0">
                    <x-heroicon-o-banknotes class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            Shift {{ $activeShift->type_label }} — Sedang Berjalan
                        </p>
                        <span class="inline-flex items-center gap-1 text-[10px] font-medium
                                     px-2 py-0.5 rounded-full
                                     bg-primary-100 dark:bg-primary-900/40
                                     text-primary-700 dark:text-primary-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse"></span>
                            Aktif
                        </span>
                    </div>
                    <p class="text-xs text-neutral-500 mt-0.5">
                        Dibuka oleh {{ $activeShift->opener->name }}
                        · {{ $activeShift->opened_at->translatedFormat('H:i, d F Y') }}
                        · Sudah {{ $activeShift->duration }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
            <div class="bg-white dark:bg-neutral-900 rounded-xl p-3
                        border border-neutral-200 dark:border-neutral-800">
                <p class="text-[11px] text-neutral-500 mb-1">Modal kas</p>
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    Rp {{ number_format($activeShift->opening_cash, 0, ',', '.') }}
                </p>
            </div>
            <div class="bg-white dark:bg-neutral-900 rounded-xl p-3
                        border border-neutral-200 dark:border-neutral-800">
                <p class="text-[11px] text-neutral-500 mb-1">Total penjualan</p>
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    Rp {{ number_format($activeShift->total_sales, 0, ',', '.') }}
                </p>
            </div>
            <div class="bg-white dark:bg-neutral-900 rounded-xl p-3
                        border border-neutral-200 dark:border-neutral-800">
                <p class="text-[11px] text-neutral-500 mb-1">Transaksi</p>
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    {{ $activeShift->total_transactions }}
                </p>
            </div>
            <div class="bg-white dark:bg-neutral-900 rounded-xl p-3
                        border border-neutral-200 dark:border-neutral-800">
                <p class="text-[11px] text-neutral-500 mb-1">Laba</p>
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">
                    Rp {{ number_format($activeShift->total_profit, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- Tutup shift --}}
        <div class="mt-4 pt-4 border-t border-primary-200 dark:border-primary-800">
            <div x-data="{ open: false }">
                <button @click="open = true" class="btn-danger !text-xs">
                    <x-heroicon-o-lock-closed class="w-3.5 h-3.5" />
                    Tutup shift (clerek)
                </button>

                {{-- Modal tutup shift --}}
                <div x-cloak x-show="open"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                    <div class="w-full max-w-sm bg-white dark:bg-neutral-900
                                border border-neutral-200 dark:border-neutral-800
                                rounded-2xl overflow-hidden">

                        <div class="flex items-center justify-between px-5 py-4
                                    border-b border-neutral-200 dark:border-neutral-800">
                            <h3 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                Tutup shift — Clerek
                            </h3>
                            <button @click="open = false">
                                <x-heroicon-s-x-mark class="w-4 h-4 text-neutral-400" />
                            </button>
                        </div>

                        <form method="POST" action="{{ route('shifts.close', $activeShift) }}"
                              x-data="{
                                  raw: '',
                                  get formatted() {
                                      if (!this.raw) return ''
                                      return new Intl.NumberFormat('id-ID').format(this.raw)
                                  },
                                  get expected() {
                                      return {{ $activeShift->opening_cash + $activeShift->total_cash }}
                                  },
                                  get diff() {
                                      return (parseInt(this.raw) || 0) - this.expected
                                  }
                              }">
                            @csrf

                            <div class="px-5 py-4 space-y-4">

                                {{-- Info kas --}}
                                <div class="bg-neutral-50 dark:bg-neutral-800 rounded-xl p-3
                                            space-y-1.5 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-neutral-500">Modal awal</span>
                                        <span class="font-medium">
                                            Rp {{ number_format($activeShift->opening_cash, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-neutral-500">Penjualan tunai</span>
                                        <span class="font-medium">
                                            Rp {{ number_format($activeShift->total_cash, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between pt-1.5
                                                border-t border-neutral-200 dark:border-neutral-700">
                                        <span class="text-neutral-500 font-medium">Seharusnya di kas</span>
                                        <span class="font-medium text-neutral-900 dark:text-neutral-100">
                                            Rp {{ number_format($activeShift->opening_cash + $activeShift->total_cash, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Input uang fisik --}}
                                <div>
                                    <label class="label">Uang fisik di kas (Rp)</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2
                                                     text-sm text-neutral-500 pointer-events-none">
                                            Rp
                                        </span>
                                        <input
                                            type="text"
                                            :value="formatted"
                                            @input="raw = $event.target.value.replace(/\D/g, '')"
                                            @keydown="
                                                if (['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight'].includes($event.key)) return;
                                                if (!/[0-9]/.test($event.key)) $event.preventDefault();
                                            "
                                            class="input pl-9"
                                            placeholder="0"
                                            required
                                        >
                                        <input type="hidden" name="closing_cash" :value="raw || 0">
                                    </div>
                                </div>

                                {{-- Selisih realtime --}}
                                <div class="flex justify-between items-center text-xs p-3 rounded-xl transition-colors"
                                     :class="{
                                         'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300': diff === 0,
                                         'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300': diff > 0,
                                         'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300': diff < 0
                                     }"
                                >
                                    <span>Selisih kas</span>
                                    <span class="font-medium" x-text="
                                        diff === 0
                                            ? 'Pas ✓'
                                            : diff > 0
                                                ? 'Lebih Rp ' + Math.abs(diff).toLocaleString('id-ID')
                                                : 'Kurang Rp ' + Math.abs(diff).toLocaleString('id-ID')
                                    "></span>
                                </div>

                                {{-- Catatan --}}
                                <div>
                                    <label class="label">Catatan (opsional)</label>
                                    <textarea
                                        name="note"
                                        rows="2"
                                        class="input resize-none"
                                        placeholder="Catatan serah terima..."
                                    ></textarea>
                                </div>

                            </div>

                            <div class="flex gap-2 px-5 py-4
                                        border-t border-neutral-200 dark:border-neutral-800">
                                <button type="button" @click="open = false"
                                        class="btn-secondary flex-1 justify-center !text-xs">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="btn-danger flex-1 justify-center !text-xs">
                                    <x-heroicon-o-lock-closed class="w-3.5 h-3.5" />
                                    Tutup shift
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@else

    {{-- Buka shift --}}
    <div class="card mb-5"
         x-data="{
             raw: '{{ $suggestedCash }}',
             get formatted() {
                 if (!this.raw || this.raw === '0') return ''
                 return new Intl.NumberFormat('id-ID').format(this.raw)
             },
             setAmount(val) {
                 this.raw = String(val)
             }
         }">

        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30
                        flex items-center justify-center shrink-0">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-500" />
            </div>
            <div>
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    Shift belum dibuka
                </p>
                <p class="text-xs text-neutral-500 mt-0.5">
                    Buka shift untuk mulai menerima transaksi
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('shifts.open') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Tipe shift --}}
                <div>
                    <label class="label">Tipe shift</label>
                    <select name="type" class="select">
                        <option value="morning">Shift Pagi</option>
                        <option value="evening">Shift Malam</option>
                    </select>
                </div>

                {{-- Modal kas --}}
                <div>
                    <label class="label">Modal kas awal (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2
                                     text-sm text-neutral-500 pointer-events-none">
                            Rp
                        </span>
                        <input
                            type="text"
                            :value="formatted"
                            @input="raw = $event.target.value.replace(/\D/g, '')"
                            @keydown="
                                if (['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight'].includes($event.key)) return;
                                if (!/[0-9]/.test($event.key)) $event.preventDefault();
                            "
                            class="input pl-9"
                            placeholder="0"
                        >
                        <input type="hidden" name="opening_cash" :value="raw || 0">
                    </div>
                </div>

            </div>

            {{-- Info kas shift sebelumnya --}}
            @if ($suggestedCash > 0)
                <div class="flex items-center gap-2 text-xs px-3 py-2.5 rounded-lg
                            bg-primary-50 dark:bg-primary-900/20
                            border border-primary-200 dark:border-primary-800
                            text-primary-700 dark:text-primary-300">
                    <x-heroicon-o-information-circle class="w-3.5 h-3.5 shrink-0" />
                    <span>
                        Kas shift sebelumnya:
                        <span class="font-medium">
                            Rp {{ number_format($suggestedCash, 0, ',', '.') }}
                        </span>
                        — sudah diisi otomatis
                    </span>
                </div>
            @endif

            {{-- Shortcut modal --}}
            <div>
                <p class="label mb-2">Shortcut modal</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ([100000, 200000, 300000, 500000, 1000000] as $amount)
                        <button
                            type="button"
                            @click="setAmount({{ $amount }})"
                            class="px-3 py-1.5 text-xs rounded-lg
                                   border border-neutral-200 dark:border-neutral-700
                                   bg-neutral-50 dark:bg-neutral-800
                                   text-neutral-700 dark:text-neutral-300
                                   hover:border-primary-400 dark:hover:border-primary-600
                                   hover:text-primary-600 dark:hover:text-primary-400
                                   transition-all duration-150"
                        >
                            Rp {{ number_format($amount, 0, ',', '.') }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Preview --}}
            <div x-show="raw && raw !== '0'"
                 class="text-xs text-neutral-500 px-3 py-2 rounded-lg
                         bg-neutral-50 dark:bg-neutral-800
                         border border-neutral-200 dark:border-neutral-700">
                Modal awal:
                <span class="font-medium text-neutral-900 dark:text-neutral-100"
                      x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(raw)">
                </span>
            </div>

            <div class="pt-1">
                <button type="submit" class="btn-primary">
                    <x-heroicon-o-lock-open class="w-4 h-4" />
                    Buka shift
                </button>
            </div>

        </form>
    </div>

@endif

{{-- Riwayat shift --}}
<div class="card p-0 overflow-hidden">
    <div class="px-4 py-3 border-b border-neutral-200 dark:border-neutral-800">
        <h3 class="text-xs font-medium text-neutral-900 dark:text-neutral-100">
            Riwayat shift
        </h3>
    </div>

    <div class="overflow-x-auto">
        <table class="table-base">
            <thead>
                <tr>
                    <th>Tipe</th>
                    <th>Dibuka oleh</th>
                    <th>Waktu buka</th>
                    <th>Waktu tutup</th>
                    <th class="text-right">Modal</th>
                    <th class="text-right">Penjualan</th>
                    <th class="text-right">Transaksi</th>
                    <th class="text-right">Selisih</th>
                    <th class="text-right">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shifts as $shift)
                    <tr>
                        <td>
                            <span class="text-xs font-medium">
                                Shift {{ $shift->type_label }}
                            </span>
                        </td>
                        <td class="text-xs">{{ $shift->opener->name }}</td>
                        <td class="text-xs whitespace-nowrap">
                            {{ $shift->opened_at->format('d/m H:i') }}
                        </td>
                        <td class="text-xs whitespace-nowrap">
                            {{ $shift->closed_at?->format('d/m H:i') ?? '-' }}
                        </td>
                        <td class="text-right text-xs">
                            Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}
                        </td>
                        <td class="text-right text-xs">
                            Rp {{ number_format($shift->total_sales, 0, ',', '.') }}
                        </td>
                        <td class="text-right text-xs">
                            {{ $shift->total_transactions }}
                        </td>
                        <td class="text-right text-xs">
                            @if ($shift->difference === null)
                                <span class="text-neutral-400">-</span>
                            @elseif ($shift->difference == 0)
                                <span class="text-primary-600 dark:text-primary-400">Pas</span>
                            @elseif ($shift->difference > 0)
                                <span class="text-blue-600 dark:text-blue-400">
                                    +Rp {{ number_format($shift->difference, 0, ',', '.') }}
                                </span>
                            @else
                                <span class="text-red-500">
                                    -Rp {{ number_format(abs($shift->difference), 0, ',', '.') }}
                                </span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if ($shift->status === 'open')
                                <span class="badge-primary">Aktif</span>
                            @else
                                <span class="badge-neutral">Tutup</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('shifts.show', $shift) }}"
                               class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-10 text-center text-xs text-neutral-400">
                            Belum ada riwayat shift
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($shifts->hasPages())
        <div class="px-4 py-3 border-t border-neutral-200 dark:border-neutral-800">
            {{ $shifts->links() }}
        </div>
    @endif
</div>

@endsection
