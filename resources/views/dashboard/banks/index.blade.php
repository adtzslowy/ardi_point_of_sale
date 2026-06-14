@extends('layouts.app')
@section('title', 'Saldo Bank & E-Wallet')

@section('content')
<div x-data="bankPage()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Saldo Bank & E-Wallet</h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Rekening tujuan transfer untuk transaksi cabang</p>
        </div>
        <button type="button" @click="openCreate()" class="btn-primary">
            <x-heroicon-o-plus class="w-4 h-4" /> Tambah rekening
        </button>
    </div>

    @if (session('success'))
        <div class="mb-4 px-4 py-3 rounded-xl text-xs bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800 text-primary-800 dark:text-primary-200">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 px-4 py-3 rounded-xl text-xs bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 px-4 py-3 rounded-xl text-xs bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
        <div class="stat-card">
            <span class="stat-label">Total saldo aktif</span>
            <p class="stat-value text-base">Rp {{ number_format($totalBalance, 0, ',', '.') }}</p>
        </div>
        <div class="stat-card">
            <span class="stat-label">Jumlah rekening</span>
            <p class="stat-value">{{ $accounts->count() }}</p>
        </div>
    </div>

    {{-- Daftar rekening --}}
    <div class="card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Tipe</th><th>Nama</th><th>No. Rekening / Akun</th><th>Pemilik</th>
                        <th class="text-right">Saldo</th><th class="text-center">Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $acc)
                        <tr>
                            <td>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium
                                    {{ $acc->type === 'ewallet'
                                        ? 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300'
                                        : 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300' }}">
                                    {{ $acc->type_label }}
                                </span>
                            </td>
                            <td class="text-xs font-medium text-neutral-900 dark:text-neutral-100">{{ $acc->bank_name }}</td>
                            <td class="text-xs text-neutral-500">{{ $acc->account_number }}</td>
                            <td class="text-xs text-neutral-500">{{ $acc->account_name }}</td>
                            <td class="text-right text-xs font-medium">Rp {{ number_format($acc->balance, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if ($acc->is_active) <span class="badge-success">Aktif</span>
                                @else <span class="badge-neutral">Nonaktif</span> @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <button type="button"
                                    @click="openEdit('{{ $acc->id }}', '{{ $acc->type }}', {{ Js::from($acc->bank_name) }}, {{ Js::from($acc->account_number) }}, {{ Js::from($acc->account_name) }}, {{ $acc->is_active ? 'true' : 'false' }})"
                                    class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Edit</button>
                                <button type="button" @click="confirmDelete('{{ $acc->id }}', '{{ $acc->bank_name }}')"
                                    class="text-xs text-red-500 hover:underline ml-2">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-xs text-neutral-400">
                                Belum ada rekening. Tambahkan bank / e-wallet sebagai tujuan transfer.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ====== MODAL TAMBAH / EDIT ====== --}}
    <div x-cloak x-show="formOpen" @keydown.escape.window="formOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div @click.outside="formOpen = false"
             class="w-full max-w-md bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-2xl overflow-hidden">
            <form :action="formAction" method="POST">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                <div class="px-5 py-4 border-b border-neutral-200 dark:border-neutral-800">
                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100"
                       x-text="mode === 'edit' ? 'Edit rekening' : 'Tambah rekening'"></p>
                    <p class="text-xs text-neutral-500 mt-0.5">Untuk cabang yang sedang aktif.</p>
                </div>

                <div class="px-5 py-4 space-y-3">
                    <div>
                        <label class="label">Tipe <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="t in [{v:'bank',l:'Bank'},{v:'ewallet',l:'E-Wallet'}]" :key="t.v">
                                <button type="button" @click="form.type = t.v"
                                    class="text-xs py-2 rounded-lg border transition-colors"
                                    :class="form.type === t.v ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium' : 'border-neutral-200 dark:border-neutral-700 text-neutral-500'"
                                    x-text="t.l"></button>
                            </template>
                        </div>
                        <input type="hidden" name="type" :value="form.type">
                    </div>

                    <div>
                        <label class="label" x-text="form.type === 'ewallet' ? 'Nama e-wallet *' : 'Nama bank *'"></label>
                        <input type="text" name="bank_name" x-model="form.bank_name" required
                               class="input" :placeholder="form.type === 'ewallet' ? 'mis: DANA, OVO, GoPay' : 'mis: BCA, Mandiri, BRI'">
                    </div>
                    <div>
                        <label class="label" x-text="form.type === 'ewallet' ? 'No. HP / akun *' : 'No. rekening *'"></label>
                        <input type="text" name="account_number" x-model="form.account_number" required class="input" placeholder="mis: 1234567890">
                    </div>
                    <div>
                        <label class="label">Nama pemilik <span class="text-red-500">*</span></label>
                        <input type="text" name="account_name" x-model="form.account_name" required class="input" placeholder="mis: Ardi Saputra">
                    </div>

                    <div x-show="mode === 'create'">
                        <label class="label">Saldo awal (Rp)</label>
                        <input type="text" inputmode="numeric"
                               :value="form.balance ? numID(form.balance) : ''"
                               @input="form.balance = parseInt($event.target.value.replace(/\D/g,'')) || 0"
                               class="input" placeholder="0">
                        <input type="hidden" name="balance" :value="form.balance">
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer pt-1">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="w-4 h-4 rounded accent-primary-600">
                        <span class="text-xs text-neutral-700 dark:text-neutral-300">Rekening aktif (tampil di kasir)</span>
                    </label>
                </div>

                <div class="flex gap-2 px-5 py-4 border-t border-neutral-200 dark:border-neutral-800">
                    <button type="button" @click="formOpen = false" class="btn-secondary flex-1 justify-center !text-xs">Batal</button>
                    <button type="submit" class="btn-primary flex-1 justify-center !text-xs">
                        <x-heroicon-o-check class="w-3.5 h-3.5" /> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ====== MODAL HAPUS ====== --}}
    <div x-cloak x-show="deleteOpen" @keydown.escape.window="deleteOpen = false"
         class="fixed inset-0 z-[55] flex items-center justify-center p-4 bg-black/50">
        <div class="w-full max-w-sm bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4">
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">Hapus rekening?</p>
                <p class="text-xs text-neutral-500">
                    Rekening <strong x-text="deleteName"></strong> akan dihapus. Jika sudah punya riwayat mutasi, rekening hanya dinonaktifkan.
                </p>
            </div>
            <div class="flex gap-2 px-5 py-4 border-t border-neutral-200 dark:border-neutral-800">
                <button type="button" @click="deleteOpen = false" class="btn-secondary flex-1 justify-center !text-xs">Batal</button>
                <form :action="deleteAction" method="POST" class="flex-1">
                    @csrf
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn-danger w-full justify-center !text-xs">Ya, hapus</button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bankPage', () => ({
        storeUrl: '{{ route('banks.store') }}',
        baseUrl: '{{ url('bank') }}',
        formOpen: false, deleteOpen: false,
        mode: 'create', formAction: '',
        deleteAction: '', deleteName: '',
        form: { type: 'bank', bank_name: '', account_number: '', account_name: '', balance: 0, is_active: true },

        numID(n) { return new Intl.NumberFormat('id-ID').format(n || 0); },

        openCreate() {
            this.mode = 'create';
            this.formAction = this.storeUrl;
            this.form = { type: 'bank', bank_name: '', account_number: '', account_name: '', balance: 0, is_active: true };
            this.formOpen = true;
        },
        openEdit(id, type, bankName, accountNumber, accountName, isActive) {
            this.mode = 'edit';
            this.formAction = this.baseUrl + '/' + id;
            this.form = {
                type: type, bank_name: bankName, account_number: accountNumber,
                account_name: accountName, balance: 0, is_active: !!isActive,
            };
            this.formOpen = true;
        },
        confirmDelete(id, name) {
            this.deleteAction = this.baseUrl + '/' + id;
            this.deleteName = name;
            this.deleteOpen = true;
        },
    }));
});
</script>
@endpush
