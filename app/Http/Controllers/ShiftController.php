<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $branchId    = auth()->user()->active_branch_id;
        $activeShift = Shift::forBranch($branchId)->open()->latest()->first();

        $lastShift = Shift::forBranch($branchId)
            ->where('status', 'closed')
            ->latest('closed_at')
            ->first();

        $suggestedCash = $lastShift?->closing_cash ?? 0;

        $shifts = Shift::with(['opener', 'closer'])
            ->forBranch($branchId)
            ->latest()
            ->paginate(15);

        return view('dashboard.shifts.index', compact('activeShift', 'shifts', 'suggestedCash'));
    }

    public function open(Request $request)
    {
        $request->validate([
            'type'         => 'required|in:morning,evening',
            'opening_cash' => 'required|integer|min:0',
        ]);

        $branchId = auth()->user()->active_branch_id;

        $already = Shift::forBranch($branchId)->open()->exists();
        if ($already) {
            return back()->with('error', 'Masih ada shift yang aktif. Tutup terlebih dahulu.');
        }

        $shift = Shift::create([
            'branch_id'    => $branchId,
            'opened_by'    => auth()->id(),
            'type'         => $request->type,
            'opening_cash' => $request->opening_cash,
            'status'       => 'open',
            'opened_at'    => now(),
        ]);

        ActivityLog::log('opened', $shift, null, $shift->toArray());

        return redirect()->route('shifts.index')
            ->with('success', "Shift {$shift->type_label} berhasil dibuka.");
    }

    public function show(Shift $shift)
    {
        $this->authorizeBranch($shift);
        $shift->load(['opener', 'closer', 'transactions.items', 'transactions.kasir']);
        return view('dashboard.shifts.show', compact('shift'));
    }

    public function close(Request $request, Shift $shift)
    {
        $this->authorizeBranch($shift);

        $request->validate([
            'closing_cash' => 'required|integer|min:0',
            'note'         => 'nullable|string|max:500',
        ]);

        if ($shift->status !== 'open') {
            return back()->with('error', 'Shift sudah ditutup.');
        }

        $shift->recalculate();
        $shift->refresh();

        $expectedCash = $shift->opening_cash + $shift->total_cash;
        $difference   = $request->closing_cash - $expectedCash;

        $before = $shift->toArray();

        $shift->update([
            'closed_by'    => auth()->id(),
            'closing_cash' => $request->closing_cash,
            'difference'   => $difference,
            'note'         => $request->note,
            'status'       => 'closed',
            'closed_at'    => now(),
        ]);

        ActivityLog::log('closed', $shift, $before, $shift->fresh()->toArray());

        $diffText = $difference == 0
            ? 'Kas pas'
            : ($difference > 0
                ? 'Lebih Rp ' . number_format(abs($difference), 0, ',', '.')
                : 'Kurang Rp ' . number_format(abs($difference), 0, ',', '.'));

        return redirect()->route('shifts.index')
            ->with('success', "Shift berhasil ditutup. {$diffText}.");
    }

    public function status()
    {
        $branchId = auth()->user()->active_branch_id;
        $shift = Shift::forBranch($branchId)->open()->latest()->first();


        if (!$shift) {
            return response()->json(['active' => false]);
        }

        $shift->recalculate();

        return response()->json([
            'active'        => true,
            'type'          => $shift->type_label,
            'opener'        => $shift->opener->name,
            'opening_cash'  => $shift->opening_cash,
            'total_cash'    => $shift->total_cash,
            'total_transfer' => $shift->total_transfer,
            'total_sales'   => $shift->total_sales,
            'total_transactions' => $shift->total_transactions,
            'opened_at'     => $shift->opened_at->translatedFormat('H:i'),
        ]);
    }

    private function authorizeBranch(Shift $shift): void
    {
        if ($shift->branch_id !== auth()->user()->active_branch_id) {
            abort(403);
        }
    }
}
