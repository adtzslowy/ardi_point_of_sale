<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->active_branch_id;

        $accounts = Bank::forBranch($branchId)
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->orderBy('type')
            ->orderBy('bank_name')
            ->get();

        $totalBalance = $accounts->where('is_active', true)->sum('balance');

        return view('dashboard.banks.index', compact('accounts', 'totalBalance'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['branch_id'] = auth()->user()->active_branch_id;

        $account = Bank::create($data);
        ActivityLog::log('created', $account, null, $account->toArray());

        return back()->with('success', "Rekening {$account->bank_name} berhasil ditambahkan.");
    }

    public function update(Request $request, Bank $bank)
    {
        $this->authorizeBranch($bank);

        $before = $bank->toArray();
        $data   = $this->validateData($request, $bank);

        // Saldo tidak diubah lewat form edit; gunakan penyesuaian saldo terpisah
        unset($data['balance']);

        $bank->update($data);
        ActivityLog::log('updated', $bank, $before, $bank->fresh()->toArray());

        return back()->with('success', "Rekening {$bank->bank_name} berhasil diperbarui.");
    }

    public function destroy(Bank $bank)
    {
        $this->authorizeBranch($bank);

        // Nonaktifkan saja bila sudah punya mutasi, agar histori tetap utuh
        if ($bank->mutations()->exists()) {
            $bank->update(['is_active' => false]);
            return back()->with('success', "Rekening {$bank->bank_name} dinonaktifkan (punya riwayat mutasi).");
        }

        ActivityLog::log('deleted', $bank, $bank->toArray());
        $bank->delete();

        return back()->with('success', "Rekening {$bank->bank_name} berhasil dihapus.");
    }

    private function validateData(Request $request, ?Bank $bank = null): array
    {
        return $request->validate([
            'type'           => 'required|in:bank,ewallet',
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'required|string|max:100',
            'balance'        => 'nullable|integer|min:0',
            'is_active'      => 'boolean',
        ], [], [
            'bank_name'      => 'nama bank / e-wallet',
            'account_number' => 'nomor rekening / akun',
            'account_name'   => 'nama pemilik',
        ]);
    }

    private function authorizeBranch(Bank $bank): void
    {
        if ($bank->branch_id !== auth()->user()->active_branch_id) {
            abort(403);
        }
    }
}
