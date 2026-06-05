<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->active_branch_id;

        $transactions = Transaction::with('kasir')
            ->forBranch($branchId)
            ->when($request->search, fn($q) => $q->where('trx_number', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date, fn($q) => $q->whereDate('created_at', $request->date))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $todayBase  = Transaction::forBranch($branchId)->completed()->whereDate('created_at', today());
        $todaySales = (clone $todayBase)->sum('total');
        $todayCount = (clone $todayBase)->count();

        $activeShift = Shift::forBranch($branchId)->open()->latest()->first();

        $products = Product::with('category')
            ->forBranch($branchId)->active()->orderBy('name')->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'sku'         => $p->sku,
                'price'       => (int) $p->price,
                'stock'       => (int) $p->stock,
                'category_id' => $p->category_id,
                'category'    => $p->category?->name,
            ]);

        $categories = Category::forBranch($branchId)->product()->active()->orderBy('name')->get();

        return view('dashboard.transactions.index', compact(
            'transactions', 'todaySales', 'todayCount', 'activeShift', 'products', 'categories'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'discount_type'      => 'required|in:none,percent,nominal',
            'discount_value'     => 'nullable|numeric|min:0',
            'payment_method'     => 'required|in:cash,transfer,mixed',
            'paid_cash'          => 'nullable|integer|min:0',
            'paid_transfer'      => 'nullable|integer|min:0',
            'note'               => 'nullable|string|max:500',
        ]);

        $branchId    = auth()->user()->active_branch_id;
        $activeShift = Shift::forBranch($branchId)->open()->latest()->first();

        if (!$activeShift) {
            return $this->fail($request, 'Shift sudah tidak aktif. Tidak bisa menyimpan transaksi.');
        }

        try {
            $transaction = DB::transaction(function () use ($data, $branchId, $activeShift) {
                $subtotal = 0; $totalProfit = 0; $prepared = [];

                foreach ($data['items'] as $row) {
                    $product = Product::where('branch_id', $branchId)->lockForUpdate()->findOrFail($row['product_id']);
                    $qty = (int) $row['qty'];
                    if ($product->stock < $qty) {
                        throw new \RuntimeException("Stok {$product->name} tidak cukup (tersisa {$product->stock}).");
                    }
                    $lineSubtotal = $product->price * $qty;
                    $lineProfit   = ($product->price - $product->cost_price) * $qty;
                    $subtotal += $lineSubtotal; $totalProfit += $lineProfit;
                    $prepared[] = ['product' => $product, 'qty' => $qty, 'subtotal' => $lineSubtotal, 'profit' => $lineProfit];
                }

                $discountType   = $data['discount_type'];
                $discountValue  = (float) ($data['discount_value'] ?? 0);
                $discountAmount = 0;
                if ($discountType === 'percent')     $discountAmount = (int) round($subtotal * min($discountValue, 100) / 100);
                elseif ($discountType === 'nominal') $discountAmount = (int) min($discountValue, $subtotal);
                $total = max($subtotal - $discountAmount, 0);

                $method = $data['payment_method'];
                $paidCash = (int) ($data['paid_cash'] ?? 0);
                $paidTransfer = (int) ($data['paid_transfer'] ?? 0);
                if ($method === 'cash')         { $paidTransfer = 0; if ($paidCash < $total) throw new \RuntimeException('Uang tunai kurang dari total.'); }
                elseif ($method === 'transfer') { $paidCash = 0; if ($paidTransfer < $total) throw new \RuntimeException('Nominal transfer kurang dari total.'); }
                else                            { if (($paidCash + $paidTransfer) < $total) throw new \RuntimeException('Total pembayaran kurang dari total belanja.'); }
                $changeAmount = max(($paidCash + $paidTransfer) - $total, 0);

                $countToday = Transaction::where('branch_id', $branchId)->whereDate('created_at', today())->count();
                $trxNumber  = 'TRX-' . now()->format('Ymd') . '-' . str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);

                $transaction = Transaction::create([
                    'branch_id' => $branchId, 'shift_id' => $activeShift->id, 'user_id' => auth()->id(),
                    'trx_number' => $trxNumber, 'subtotal' => $subtotal,
                    'discount_type' => $discountType, 'discount_value' => $discountValue, 'discount_amount' => $discountAmount,
                    'total' => $total, 'payment_method' => $method,
                    'paid_cash' => $paidCash, 'paid_transfer' => $paidTransfer, 'change_amount' => $changeAmount,
                    'total_profit' => $totalProfit, 'status' => 'completed', 'note' => $data['note'] ?? null,
                ]);

                foreach ($prepared as $item) {
                    $product = $item['product'];
                    TransactionItem::create([
                        'transaction_id' => $transaction->id, 'item_type' => 'product',
                        'item_id' => $product->id, 'item_name' => $product->name,
                        'unit_price' => $product->price, 'cost_price' => $product->cost_price,
                        'qty' => $item['qty'], 'subtotal' => $item['subtotal'], 'profit' => $item['profit'],
                    ]);
                    $before = $product->stock; $after = $before - $item['qty'];
                    $product->update(['stock' => $after]);
                    StockMovement::create([
                        'branch_id' => $branchId, 'product_id' => $product->id, 'user_id' => auth()->id(),
                        'type' => 'out', 'qty_before' => $before, 'qty_change' => -$item['qty'], 'qty_after' => $after,
                        'reference' => $trxNumber, 'note' => 'Penjualan',
                    ]);
                }
                return $transaction;
            });
        } catch (\RuntimeException $e) {
            return $this->fail($request, $e->getMessage());
        }

        ActivityLog::log('created', $transaction, null, $transaction->toArray());
        $transaction->load(['items', 'kasir']);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'receipt' => $this->receiptPayload($transaction)], 201);
        }
        return redirect()->route('transactions.index')->with('success', "Transaksi {$transaction->trx_number} disimpan.");
    }

    public function show(Request $request, Transaction $transaction)
    {
        $this->authorizeBranch($transaction);
        $transaction->load(['items', 'kasir', 'shift']);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'receipt' => $this->receiptPayload($transaction)]);
        }
        return view('dashboard.transactions.show', compact('transaction'));
    }

    public function void(Request $request, Transaction $transaction)
    {
        $this->authorizeBranch($transaction);
        $request->validate(['void_reason' => 'required|string|max:500']);

        if ($transaction->status !== 'completed') {
            return $this->fail($request, 'Transaksi ini tidak bisa dibatalkan.');
        }

        DB::transaction(function () use ($request, $transaction) {
            $before = $transaction->toArray();
            foreach ($transaction->items as $item) {
                if ($item->item_type !== 'product') continue;
                $product = Product::find($item->item_id);
                if (!$product) continue;
                $sb = $product->stock; $sa = $sb + $item->qty;
                $product->update(['stock' => $sa]);
                StockMovement::create([
                    'branch_id' => $transaction->branch_id, 'product_id' => $product->id, 'user_id' => auth()->id(),
                    'type' => 'in', 'qty_before' => $sb, 'qty_change' => $item->qty, 'qty_after' => $sa,
                    'reference' => 'VOID-' . $transaction->trx_number, 'note' => 'Pembatalan transaksi',
                ]);
            }
            $transaction->update(['status' => 'void', 'void_reason' => $request->void_reason]);
            ActivityLog::log('voided', $transaction, $before, $transaction->fresh()->toArray());
        });

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => 'void']);
        }
        return back()->with('success', "Transaksi {$transaction->trx_number} dibatalkan & stok dikembalikan.");
    }

    private function receiptPayload(Transaction $t): array
    {
        return [
            'id'              => $t->id,
            'trx_number'      => $t->trx_number,
            'created_at'      => $t->created_at->translatedFormat('d M Y H:i'),
            'kasir'           => $t->kasir?->name ?? '-',
            'status'          => $t->status,
            'payment_label'   => $t->payment_label,
            'subtotal'        => (int) $t->subtotal,
            'discount_type'   => $t->discount_type,
            'discount_value'  => (float) $t->discount_value,
            'discount_amount' => (int) $t->discount_amount,
            'total'           => (int) $t->total,
            'paid_cash'       => (int) $t->paid_cash,
            'paid_transfer'   => (int) $t->paid_transfer,
            'change_amount'   => (int) $t->change_amount,
            'note'            => $t->note,
            'void_reason'     => $t->void_reason,
            'items'           => $t->items->map(fn($i) => [
                'name'       => $i->item_name,
                'unit_price' => (int) $i->unit_price,
                'qty'        => (int) $i->qty,
                'subtotal'   => (int) $i->subtotal,
            ])->values(),
        ];
    }

    private function fail(Request $request, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }
        return back()->withInput()->with('error', $message);
    }

    private function authorizeBranch(Transaction $transaction): void
    {
        if ($transaction->branch_id !== auth()->user()->active_branch_id) {
            abort(403);
        }
    }
}
