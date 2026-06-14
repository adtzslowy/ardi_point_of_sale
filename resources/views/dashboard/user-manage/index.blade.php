@extends('layouts.app')
@section('title', 'Karyawan')

@section('content')
<div x-data="karyawanPage()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Karyawan</h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Kelola akun owner & kasir tiap cabang</p>
        </div>
        <button type="button" @click="openCreate()" class="btn-primary">
            <x-heroicon-o-user-plus class="w-4 h-4" /> Tambah karyawan
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
            <span class="stat-label">Total karyawan</span>
            <p class="stat-value">{{ $users->total() }}</p>
        </div>
        <div class="stat-card">
            <span class="stat-label">Aktif</span>
            <p class="stat-value">{{ $totalActive }}</p>
        </div>
        <div class="stat-card">
            <span class="stat-label">Owner</span>
            <p class="stat-value">{{ $ownerCount }}</p>
        </div>
    </div>

    {{-- Daftar --}}
    <div class="card p-0 overflow-hidden">
        <form method="GET" action="{{ route('user-manage.index') }}"
              class="flex flex-wrap items-center gap-2 px-4 py-3 border-b border-neutral-200 dark:border-neutral-800">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / user ID / email..." class="input !w-56 !py-1.5 text-xs">
            <select name="branch" class="select !w-40 !py-1.5 text-xs">
                <option value="">Semua cabang</option>
                @foreach ($branches as $b)
                    <option value="{{ $b->id }}" @selected(request('branch')===$b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
            <select name="role" class="select !w-32 !py-1.5 text-xs">
                <option value="">Semua role</option>
                @foreach ($roles as $r)
                    <option value="{{ $r }}" @selected(request('role')===$r)>{{ ucfirst($r) }}</option>
                @endforeach
            </select>
            <select name="status" class="select !w-32 !py-1.5 text-xs">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status')==='active')>Aktif</option>
                <option value="inactive" @selected(request('status')==='inactive')>Nonaktif</option>
            </select>
            <button type="submit" class="btn-primary !py-1.5 !text-xs">Filter</button>
            @if (request()->hasAny(['search','branch','role','status']))
                <a href="{{ route('user-manage.index') }}" class="btn-secondary !py-1.5 !text-xs">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Nama</th><th>User ID</th><th>Email</th><th>Cabang</th>
                        <th class="text-center">Role</th><th class="text-center">Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        @php $role = $u->roles->first()?->name ?? '-'; @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full shrink-0 bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center">
                                        <span class="text-[11px] font-medium text-primary-700 dark:text-primary-300">
                                            {{ strtoupper(substr($u->name, 0, 2)) }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100 truncate">{{ $u->name }}</p>
                                        @if ($u->id === auth()->id())
                                            <p class="text-[10px] text-neutral-400">Akun kamu</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-xs text-neutral-500">{{ $u->user_id ?? '-' }}</td>
                            <td class="text-xs text-neutral-500">{{ $u->email }}</td>
                            <td class="text-xs text-neutral-500">{{ $u->branch?->name ?? '— (roaming)' }}</td>
                            <td class="text-center">
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium
                                    {{ $role === 'owner'
                                        ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
                                        : 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300' }}">
                                    {{ ucfirst($role) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if ($u->is_active) <span class="badge-success">Aktif</span>
                                @else <span class="badge-neutral">Nonaktif</span> @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <button type="button"
                                    @click="openEdit('{{ $u->id }}', {{ Js::from($u->name) }}, {{ Js::from($u->user_id) }}, {{ Js::from($u->email) }}, '{{ $role }}', '{{ $u->branch_id }}', {{ $u->is_active ? 'true' : 'false' }})"
                                    class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Edit</button>
                                <button type="button" @click="openReset('{{ $u->id }}', {{ Js::from($u->name) }})"
                                    class="text-xs text-neutral-500 hover:underline ml-2">Reset PW</button>
                                @if ($u->id !== auth()->id())
                                    <button type="button" @click="confirmDelete('{{ $u->id }}', {{ Js::from($u->name) }})"
                                        class="text-xs text-red-500 hover:underline ml-2">Hapus</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-xs text-neutral-400">Belum ada karyawan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200 dark:border-neutral-800">{{ $users->links() }}</div>
        @endif
    </div>

    {{-- ====== MODAL TAMBAH / EDIT ====== --}}
    <div x-cloak x-show="formOpen" @keydown.escape.window="formOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div @click.outside="formOpen = false"
             class="w-full max-w-md max-h-[92vh] overflow-y-auto bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-2xl">
            <form :action="formAction" method="POST">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                <div class="px-5 py-4 border-b border-neutral-200 dark:border-neutral-800">
                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100"
                       x-text="mode === 'edit' ? 'Edit karyawan' : 'Tambah karyawan'"></p>
                </div>

                <div class="px-5 py-4 space-y-3">
                    <div>
                        <label class="label">Role <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="r in [{v:'kasir',l:'Kasir'},{v:'owner',l:'Owner'}]" :key="r.v">
                                <button type="button" @click="form.role = r.v"
                                    class="text-xs py-2 rounded-lg border transition-colors"
                                    :class="form.role === r.v ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium' : 'border-neutral-200 dark:border-neutral-700 text-neutral-500'"
                                    x-text="r.l"></button>
                            </template>
                        </div>
                        <input type="hidden" name="role" :value="form.role">
                    </div>

                    <div>
                        <label class="label">Nama lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="name" x-model="form.name" required class="input" placeholder="mis: Rini Kasir">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">User ID (login) <span class="text-red-500">*</span></label>
                            <input type="text" name="user_id" x-model="form.user_id" required class="input" placeholder="mis: kasir1">
                        </div>
                        <div>
                            <label class="label">
                                Cabang
                                <span x-show="form.role !== 'owner'" class="text-red-500">*</span>
                                <span x-show="form.role === 'owner'" x-cloak class="text-neutral-400">(opsional)</span>
                            </label>
                            <select name="branch_id" x-model="form.branch_id" class="select w-full">
                                <option value="">{{ $branches->isEmpty() ? 'Tidak ada cabang' : '-- Pilih cabang --' }}</option>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="label">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" x-model="form.email" required class="input" placeholder="mis: rini@ardi.id">
                    </div>

                    <div>
                        <label class="label">
                            Password
                            <span x-show="mode === 'create'" class="text-red-500">*</span>
                            <span x-show="mode === 'edit'" x-cloak class="text-neutral-400">(kosongkan jika tidak diubah)</span>
                        </label>
                        <input type="password" name="password" x-model="form.password" :required="mode === 'create'"
                               class="input" placeholder="Minimal 6 karakter" autocomplete="new-password">
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer pt-1">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="w-4 h-4 rounded accent-primary-600">
                        <span class="text-xs text-neutral-700 dark:text-neutral-300">Akun aktif (boleh login)</span>
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

    {{-- ====== MODAL RESET PASSWORD ====== --}}
    <div x-cloak x-show="resetOpen" @keydown.escape.window="resetOpen = false"
         class="fixed inset-0 z-[55] flex items-center justify-center p-4 bg-black/50">
        <div class="w-full max-w-sm bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-2xl overflow-hidden">
            <form :action="resetAction" method="POST">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="px-5 py-4 space-y-3">
                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Reset password</p>
                    <p class="text-xs text-neutral-500">Atur password baru untuk <strong x-text="resetName"></strong>.</p>
                    <div>
                        <label class="label">Password baru</label>
                        <input type="password" name="password" required minlength="6" class="input" placeholder="Minimal 6 karakter" autocomplete="new-password">
                    </div>
                    <div>
                        <label class="label">Ulangi password</label>
                        <input type="password" name="password_confirmation" required minlength="6" class="input" placeholder="Ulangi password" autocomplete="new-password">
                    </div>
                </div>
                <div class="flex gap-2 px-5 py-4 border-t border-neutral-200 dark:border-neutral-800">
                    <button type="button" @click="resetOpen = false" class="btn-secondary flex-1 justify-center !text-xs">Batal</button>
                    <button type="submit" class="btn-primary flex-1 justify-center !text-xs">Simpan password</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ====== MODAL HAPUS ====== --}}
    <div x-cloak x-show="deleteOpen" @keydown.escape.window="deleteOpen = false"
         class="fixed inset-0 z-[55] flex items-center justify-center p-4 bg-black/50">
        <div class="w-full max-w-sm bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4">
                <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">Hapus karyawan?</p>
                <p class="text-xs text-neutral-500">
                    Akun <strong x-text="deleteName"></strong> akan dihapus permanen. Data transaksi yang pernah dibuat tetap ada.
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
    Alpine.data('karyawanPage', () => ({
        storeUrl: '{{ route('user-manage.store') }}',
        baseUrl: '{{ url('user-management') }}',
        formOpen: false, resetOpen: false, deleteOpen: false,
        mode: 'create', formAction: '',
        resetAction: '', resetName: '',
        deleteAction: '', deleteName: '',
        form: { role: 'kasir', name: '', user_id: '', email: '', branch_id: '', password: '', is_active: true },

        openCreate() {
            this.mode = 'create';
            this.formAction = this.storeUrl;
            this.form = { role: 'kasir', name: '', user_id: '', email: '', branch_id: '', password: '', is_active: true };
            this.formOpen = true;
        },
        openEdit(id, name, userId, email, role, branchId, isActive) {
            this.mode = 'edit';
            this.formAction = this.baseUrl + '/' + id;
            this.form = {
                role: role, name: name, user_id: userId || '', email: email,
                branch_id: branchId || '', password: '', is_active: !!isActive,
            };
            this.formOpen = true;
        },
        openReset(id, name) {
            this.resetAction = this.baseUrl + '/' + id + '/reset-password';
            this.resetName = name;
            this.resetOpen = true;
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
