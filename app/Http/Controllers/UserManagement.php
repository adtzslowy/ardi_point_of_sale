<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branche;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserManagement extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('branch', 'roles')
            ->when($request->search, fn ($q) =>
                $q->where(fn ($s) =>
                    $s->where('name', 'like', "%{$request->search}%")
                      ->orWhere('user_id', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%")
                )
            )
            ->when($request->branch, fn ($q) => $q->where('branch_id', $request->branch))
            ->when($request->role, fn ($q) =>
                $q->whereHas('roles', fn ($r) => $r->where('name', $request->role))
            )
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $branches    = Branche::where('is_active', true)->orderBy('name')->get();
        $roles       = Role::orderBy('name')->pluck('name');
        $totalActive = User::where('is_active', true)->count();
        $ownerCount  = User::role('owner')->count();

        return view('dashboard.user-manage.index', compact(
            'users', 'branches', 'roles', 'totalActive', 'ownerCount'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $user = User::create([
            'name'      => $data['name'],
            'user_id'   => $data['user_id'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'branch_id' => $data['role'] === 'owner' ? ($data['branch_id'] ?? null) : $data['branch_id'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->syncRoles($data['role']);
        ActivityLog::log('created', $user, null, $user->toArray());

        return back()->with('success', "Karyawan {$user->name} berhasil ditambahkan.");
    }

    public function update(Request $request, User $user)
    {
        $before = $user->toArray();
        $data   = $this->validateData($request, $user);

        // Jangan sampai owner terakhir kehilangan akses
        if ($user->hasRole('owner') && $data['role'] !== 'owner' && User::role('owner')->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'Tidak bisa mengubah role owner terakhir. Harus selalu ada minimal satu owner.',
            ]);
        }

        $user->update([
            'name'      => $data['name'],
            'user_id'   => $data['user_id'],
            'email'     => $data['email'],
            'branch_id' => $data['role'] === 'owner' ? ($data['branch_id'] ?? null) : $data['branch_id'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if (!empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles($data['role']);
        ActivityLog::log('updated', $user, $before, $user->fresh()->toArray());

        return back()->with('success', "Data {$user->name} berhasil diperbarui.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user->update(['password' => Hash::make($request->password)]);
        ActivityLog::log('password_reset', $user, null, ['user_id' => $user->user_id]);

        return back()->with('success', "Password {$user->name} berhasil direset.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
        }

        if ($user->hasRole('owner') && User::role('owner')->count() <= 1) {
            return back()->with('error', 'Tidak bisa menghapus owner terakhir.');
        }

        ActivityLog::log('deleted', $user, $user->toArray());
        $user->delete();

        return back()->with('success', "Karyawan {$user->name} berhasil dihapus.");
    }

    private function validateData(Request $request, ?User $user = null): array
    {
        $userId = $user?->id;

        return $request->validate([
            'name'      => 'required|string|max:255',
            'user_id'   => ['required', 'string', 'max:50', Rule::unique('users', 'user_id')->ignore($userId)],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role'      => ['required', Rule::in(['owner', 'kasir'])],
            'branch_id' => [
                'nullable',
                Rule::requiredIf(fn () => $request->role !== 'owner'),
                'exists:branches,id',
            ],
            'password'  => $user
                ? 'nullable|string|min:6'
                : 'required|string|min:6',
            'is_active' => 'boolean',
        ], [], [
            'user_id'   => 'user ID (untuk login)',
            'branch_id' => 'cabang',
        ]);
    }
}
