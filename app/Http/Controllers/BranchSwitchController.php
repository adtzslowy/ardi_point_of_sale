<?php

namespace App\Http\Controllers;

use App\Models\Branche;
use Illuminate\Http\Request;

class BranchSwitchController extends Controller
{
    public function switch(Request $request)
    {
        if (!auth()->user()->hasRole('owner')) {
            return redirect()->back()->with('error', 'Akses ditolak. Hanya Owner yang diizinkan.');
        }

        $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        $branch = Branche::findOrFail($request->branch_id);

        if (!$branch->is_active) {
            return redirect()->back()->with('error', 'Cabang ini sedang tidak aktif.');
        }

        $request->session()->put('active_branch_id', $branch->id);

        return redirect()->route('dashboard')->with('success', "Berhasil beralih ke cabang {$branch->name}");
    }
}
