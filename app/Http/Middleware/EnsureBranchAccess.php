<?php

namespace App\Http\Middleware;

use App\Models\Branche;
use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (!$user->is_active) {
            return $this->kick($request, 'Akun kamu tidak aktif. Hubungi owner.');
        }

        $isOwner = $user->hasRole('owner');

        // Non-owner wajib terhubung ke satu cabang; owner bebas roaming.
        if (!$isOwner && !$user->branch_id) {
            return $this->kick($request, 'Akun belum terhubung ke cabang manapun.');
        }

        if ($isOwner) {
            $activeBranch = $this->resolveOwnerBranch($user);
        } else {
            $activeBranch = Branche::find($user->branch_id);
        }

        if (!$activeBranch || !($activeBranch->is_active ?? true)) {
            return $this->kick($request, 'Cabang tidak aktif atau tidak ditemukan.');
        }

        if (session('active_branch_id') !== $activeBranch->id) {
            session(['active_branch_id' => $activeBranch->id]);
        }

        view()->share('activeBranch', $activeBranch);
        view()->share(
            'lowStockCount',
            Product::forBranch($activeBranch->id)->lowStock()->count(),
        );

        return $next($request);
    }

    /**
     * Tentukan cabang aktif untuk owner (tidak terikat branch_id).
     * Prioritas: cabang dari session -> branch_id owner (jika ada) -> cabang aktif pertama.
     * Hanya menerima cabang yang aktif.
     */
    private function resolveOwnerBranch($user): ?Branche
    {
        foreach ([session('active_branch_id'), $user->branch_id] as $branchId) {
            if ($branchId) {
                $branch = Branche::where('is_active', true)->find($branchId);
                if ($branch) {
                    return $branch;
                }
            }
        }

        return Branche::where('is_active', true)->orderBy('name')->first();
    }

    /**
     * Helper untuk mengeluarkan user secara aman jika terjadi masalah validasi
     */
    private function kick(Request $request, string $message)
    {
        Auth::logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return redirect()->route('login')->with('error', $message);
    }
}
