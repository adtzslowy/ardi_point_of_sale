<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bank;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionReturn;
use App\Models\TransactionReturnItem;
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

        $bankAccounts = Bank::forBranch($branchId)->active()
            ->orderBy('type')->orderBy('bank_name')->get()
            ->map(fn ($b) => [
                'id'         => $b->id,
                'type'       => $b->type,
                'type_label' => $b->type_label,
                'bank_name'  => $b->bank_name,
                'account_number' => $b->account_number,
                'account_name'   => $b->account_name,
            ]);

        return view('dashboard.transactions.index', compact(
            'transactions', 'todaySales', 'todayCount', 'activeShift', 'products', 'categories', 'bankAccounts'
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
            'bank_account_id'    => 'nullable|exists:bank_accounts,id',
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

                // Tujuan transfer (bank / e-wallet) wajib bila ada nominal transfer
                $bankAccount = null;
                if ($paidTransfer > 0) {
                    if (empty($data['bank_account_id'])) {
                        throw new \RuntimeException('Pilih tujuan transfer (bank / e-wallet) terlebih dahulu.');
                    }
                    $bankAccount = Bank::forBranch($branchId)->active()->lockForUpdate()->find($data['bank_account_id']);
                    if (!$bankAccount) {
                        throw new \RuntimeException('Tujuan transfer tidak valid atau tidak aktif.');
                    }
                }

                $countToday = Transaction::where('branch_id', $branchId)->whereDate('created_at', today())->count();
                $trxNumber  = 'TRX-' . now()->format('Ymd') . '-' . str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);

                $transaction = Transaction::create([
                    'branch_id' => $branchId, 'shift_id' => $activeShift->id, 'user_id' => auth()->id(),
                    'trx_number' => $trxNumber, 'subtotal' => $subtotal,
                    'discount_type' => $discountType, 'discount_value' => $discountValue, 'discount_amount' => $discountAmount,
                    'total' => $total, 'payment_method' => $method,
                    'paid_cash' => $paidCash, 'paid_transfer' => $paidTransfer,
                    'bank_account_id' => $bankAccount?->id, 'change_amount' => $changeAmount,
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

                // Catat uang masuk ke rekening tujuan transfer
                if ($bankAccount && $paidTransfer > 0) {
                    $bankAccount->applyMutation('in', $paidTransfer, "Penjualan {$trxNumber}");
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
                // Rita: kembalikan saldo deposit (modal) & stok produk voucher (jumlah = nominal).
                if ($item->item_type === 'service') {
                    $service = Service::withTrashed()->find($item->item_id);
                    if ($service && $service->kind === 'rita') {
                        $service->increment('rita_balance', (int) $item->cost_price);
                        if ($service->product_id && $item->nominal) {
                            $product = Product::find($service->product_id);
                            if ($product) {
                                $sb = $product->stock; $sa = $sb + (int) $item->nominal;
                                $product->update(['stock' => $sa]);
                                StockMovement::create([
                                    'branch_id' => $transaction->branch_id, 'product_id' => $product->id, 'user_id' => auth()->id(),
                                    'type' => 'in', 'qty_before' => $sb, 'qty_change' => (int) $item->nominal, 'qty_after' => $sa,
                                    'reference' => 'VOID-' . $transaction->trx_number, 'note' => 'Pembatalan Rita',
                                ]);
                            }
                        }
                    }
                    continue;
                }
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

            // Balikkan pergerakan saldo bank
            if ($transaction->bank_account_id) {
                $account = Bank::lockForUpdate()->find($transaction->bank_account_id);
                if ($account) {
                    if ($transaction->paid_transfer > 0) {
                        // Penjualan via transfer: uang sempat masuk -> tarik keluar
                        $account->applyMutation('out', (int) $transaction->paid_transfer, "Pembatalan {$transaction->trx_number}");
                    } else {
                        // Jasa keuangan (tarik/setor): balikkan mutasi nominal
                        foreach ($transaction->items as $item) {
                            if ($item->item_type !== 'service' || !$item->nominal) continue;
                            $service = Service::withTrashed()->find($item->item_id);
                            if (!$service) continue;
                            if ($service->cash_direction === 'tarik') {
                                $account->applyMutation('in', (int) $item->nominal, "Pembatalan {$transaction->trx_number}");
                            } elseif ($service->cash_direction === 'setor') {
                                $account->applyMutation('out', (int) $item->nominal, "Pembatalan {$transaction->trx_number}");
                            }
                        }
                    }
                }
            }

            $transaction->update(['status' => 'void', 'void_reason' => $request->void_reason]);

            // Hitung ulang ringkasan shift agar kas/penjualan tak lagi memuat transaksi batal.
            $transaction->shift?->recalculate();

            ActivityLog::log('voided', $transaction, $before, $transaction->fresh()->toArray());
        });

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => 'void']);
        }
        return back()->with('success', "Transaksi {$transaction->trx_number} dibatalkan & stok dikembalikan.");
    }

    public function processReturn(Request $request, Transaction $transaction)
    {
        $this->authorizeBranch($transaction);

        $data = $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.item_id'       => 'required|exists:transaction_items,id',
            'items.*.qty'           => 'required|integer|min:1',
            'refund_method'         => 'required|in:cash,transfer',
            'bank_account_id'       => 'nullable|exists:bank_accounts,id',
            'reason'                => 'required|string|max:500',
        ]);

        if ($transaction->status !== 'completed') {
            return $this->fail($request, 'Hanya transaksi selesai yang bisa diretur.');
        }

        try {
            $return = DB::transaction(function () use ($data, $transaction) {
                $branchId = $transaction->branch_id;
                $prepared = []; $totalRefund = 0;

                foreach ($data['items'] as $row) {
                    $item = TransactionItem::where('transaction_id', $transaction->id)
                        ->lockForUpdate()->findOrFail($row['item_id']);
                    $qty = (int) $row['qty'];

                    if ($qty > $item->returnable_qty) {
                        throw new \RuntimeException("Jumlah retur {$item->item_name} melebihi sisa yang bisa diretur.");
                    }

                    $lineRefund   = $item->unit_price * $qty;
                    $totalRefund += $lineRefund;
                    $prepared[]   = ['item' => $item, 'qty' => $qty, 'refund' => $lineRefund];
                }

                if ($totalRefund <= 0) {
                    throw new \RuntimeException('Tidak ada item yang diretur.');
                }

                // Tujuan pengembalian via transfer
                $bankAccount = null;
                if ($data['refund_method'] === 'transfer') {
                    if (empty($data['bank_account_id'])) {
                        throw new \RuntimeException('Pilih rekening sumber pengembalian transfer.');
                    }
                    $bankAccount = Bank::forBranch($branchId)->active()->lockForUpdate()->find($data['bank_account_id']);
                    if (!$bankAccount) {
                        throw new \RuntimeException('Rekening pengembalian tidak valid atau tidak aktif.');
                    }
                }

                $countToday    = TransactionReturn::where('branch_id', $branchId)->whereDate('created_at', today())->count();
                $returnNumber  = 'RTR-' . now()->format('Ymd') . '-' . str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);

                $return = TransactionReturn::create([
                    'branch_id'       => $branchId,
                    'transaction_id'  => $transaction->id,
                    'user_id'         => auth()->id(),
                    'return_number'   => $returnNumber,
                    'total_refund'    => $totalRefund,
                    'refund_method'   => $data['refund_method'],
                    'bank_account_id' => $bankAccount?->id,
                    'reason'          => $data['reason'],
                ]);

                foreach ($prepared as $p) {
                    $item = $p['item'];

                    TransactionReturnItem::create([
                        'transaction_return_id' => $return->id,
                        'transaction_item_id'   => $item->id,
                        'product_id'            => $item->item_type === 'product' ? $item->item_id : null,
                        'item_name'             => $item->item_name,
                        'unit_price'            => $item->unit_price,
                        'qty'                   => $p['qty'],
                        'subtotal'              => $p['refund'],
                    ]);

                    $item->increment('returned_qty', $p['qty']);

                    // Kembalikan stok produk
                    if ($item->item_type === 'product') {
                        $product = Product::where('branch_id', $branchId)->lockForUpdate()->find($item->item_id);
                        if ($product) {
                            $sb = $product->stock; $sa = $sb + $p['qty'];
                            $product->update(['stock' => $sa]);
                            StockMovement::create([
                                'branch_id' => $branchId, 'product_id' => $product->id, 'user_id' => auth()->id(),
                                'type' => 'in', 'qty_before' => $sb, 'qty_change' => $p['qty'], 'qty_after' => $sa,
                                'reference' => $returnNumber, 'note' => 'Retur barang',
                            ]);
                        }
                    }
                }

                // Tarik dana keluar rekening bila pengembalian via transfer
                if ($bankAccount) {
                    $bankAccount->applyMutation('out', $totalRefund, "Retur {$returnNumber}");
                }

                // Tandai "return" bila seluruh item sudah diretur penuh
                $allReturned = $transaction->items()->get()
                    ->every(fn ($i) => $i->returned_qty >= $i->qty);
                if ($allReturned) {
                    $transaction->update(['status' => 'return']);
                    // Hitung ulang ringkasan shift: transaksi yang diretur penuh tak lagi dihitung.
                    $transaction->shift?->recalculate();
                }

                ActivityLog::log('returned', $return, null, $return->toArray());

                return $return;
            });
        } catch (\RuntimeException $e) {
            return $this->fail($request, $e->getMessage());
        }

        $transaction->refresh();

        if ($request->wantsJson()) {
            return response()->json([
                'ok'      => true,
                'return'  => [
                    'return_number' => $return->return_number,
                    'total_refund'  => (int) $return->total_refund,
                    'refund_method' => $return->refund_method,
                ],
                'receipt' => $this->receiptPayload($transaction->load(['items', 'kasir', 'bankAccount'])),
            ]);
        }

        return back()->with('success', "Retur {$return->return_number} berhasil. Dana dikembalikan Rp " . number_format($return->total_refund, 0, ',', '.') . '.');
    }

    private function receiptPayload(Transaction $t): array
    {
        $t->loadMissing('bankAccount');

        return [
            'id'              => $t->id,
            'trx_number'      => $t->trx_number,
            'created_at'      => $t->created_at->translatedFormat('d M Y H:i'),
            'kasir'           => $t->kasir?->name ?? '-',
            'status'          => $t->status,
            'payment_method'  => $t->payment_method,
            'payment_label'   => $t->payment_label,
            'bank_account'    => $t->bankAccount ? [
                'id'         => $t->bankAccount->id,
                'type_label' => $t->bankAccount->type_label,
                'bank_name'  => $t->bankAccount->bank_name,
                'account_number' => $t->bankAccount->account_number,
            ] : null,
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
                'id'            => $i->id,
                'name'          => $i->item_name,
                'item_type'     => $i->item_type,
                'unit_price'    => (int) $i->unit_price,
                'qty'           => (int) $i->qty,
                'returned_qty'  => (int) $i->returned_qty,
                'returnable'    => (int) $i->returnable_qty,
                'subtotal'      => (int) $i->subtotal,
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
