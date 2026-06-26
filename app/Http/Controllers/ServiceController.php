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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->active_branch_id;

        $services = Service::with('category', 'product')
            ->forBranch($branchId)
            ->active()
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
            )
            ->orderBy('name')
            ->get();

        // Kategori layanan diurutkan sesuai sort_order (PPOB, Bank, E-Wallet, dst).
        $categories = Category::forBranch($branchId)->service()->active()
            ->orderBy('sort_order')->orderBy('name')->get();

        // Kelompokkan layanan per kategori; hanya tampilkan kategori yang ada isinya.
        $groups = $categories
            ->map(fn ($cat) => [
                'category' => $cat,
                'items'    => $services->where('category_id', $cat->id)->values(),
            ])
            ->filter(fn ($g) => $g['items']->isNotEmpty())
            ->values();

        // Layanan tanpa kategori dikumpulkan terpisah.
        $uncategorized = $services->whereNull('category_id')->values();

        $servisCount  = $services->where('kind', 'servis')->count();
        $financeCount = $services->where('kind', 'keuangan')->count();
        $activeShift  = Shift::forBranch($branchId)->open()->latest()->first();

        $bankAccounts = Bank::forBranch($branchId)->active()
            ->orderBy('type')->orderBy('bank_name')->get()
            ->map(fn ($b) => [
                'id'         => $b->id,
                'type_label' => $b->type_label,
                'bank_name'  => $b->bank_name,
                'account_number' => $b->account_number,
            ]);

        return view('dashboard.services.index', compact(
            'groups', 'uncategorized', 'servisCount', 'financeCount', 'activeShift', 'bankAccounts'
        ));
    }

    public function create()
    {
        $branchId   = auth()->user()->active_branch_id;
        $categories = Category::forBranch($branchId)->service()->active()->orderBy('name')->get();
        $products   = Product::forBranch($branchId)->active()->orderBy('name')->get();
        return view('dashboard.services.create', compact('categories', 'products'));
    }

    public function store(Request $request)
    {
        $data = $this->validateService($request);
        $data['branch_id'] = auth()->user()->active_branch_id;

        $service = Service::create($data);
        ActivityLog::log('created', $service, null, $service->toArray());

        return redirect()->route('services.index')
            ->with('success', "Jasa {$service->name} berhasil ditambahkan.");
    }

    public function edit(Service $service)
    {
        $this->authorizeBranch($service);
        $categories = Category::forBranch($service->branch_id)->service()->active()->orderBy('name')->get();
        $products   = Product::forBranch($service->branch_id)->active()->orderBy('name')->get();
        return view('dashboard.services.edit', compact('service', 'categories', 'products'));
    }

    public function update(Request $request, Service $service)
    {
        $this->authorizeBranch($service);

        $before = $service->toArray();
        $data   = $this->validateService($request);

        $service->update($data);
        ActivityLog::log('updated', $service, $before, $service->fresh()->toArray());

        return redirect()->route('services.index')
            ->with('success', "Jasa {$service->name} berhasil diupdate.");
    }

    public function destroy(Service $service)
    {
        $this->authorizeBranch($service);
        ActivityLog::log('deleted', $service, $service->toArray());
        $service->delete();
        return redirect()->route('services.index')
            ->with('success', "Jasa {$service->name} berhasil dihapus.");
    }

    public function sell(Request $request, Service $service)
    {
        $this->authorizeBranch($service);

        $branchId    = auth()->user()->active_branch_id;
        $activeShift = Shift::forBranch($branchId)->open()->latest()->first();

        if (!$activeShift) {
            return back()->with('error', 'Shift belum dibuka. Buka shift dulu untuk mencatat jasa.');
        }

        $movesCash = $service->kind === 'keuangan' && in_array($service->cash_direction, ['tarik', 'setor']);

        if ($service->kind === 'keuangan') {
            $rules = [
                'nominal' => 'required|integer|min:1',
                'fee'     => 'required|integer|min:0',
                'note'    => 'nullable|string|max:500',
            ];
            if ($movesCash) {
                $rules['bank_account_id'] = 'required|exists:bank_accounts,id';
            } else {
                $rules['payment_method'] = 'required|in:cash,transfer';
                $rules['bank_account_id'] = 'nullable|exists:bank_accounts,id';
            }
            $data = $request->validate($rules);

            $qty     = 1;
            $nominal = (int) $data['nominal'];
            $fee     = (int) $data['fee'];

            if ($movesCash) {
                // Tarik/setor: nilai transaksi = fee; pergerakan nominal dihitung khusus di bawah.
                $unit     = $fee;
                $subtotal = $fee;
                $profit   = $fee;
                $cost     = 0;
            } else {
                // Fee-saja (pulsa, tagihan PLN, token): pelanggan bayar nominal + fee.
                $unit     = $nominal + $fee;   // total yang diserahkan pelanggan
                $subtotal = $unit;
                $profit   = $fee;              // profit konter = fee
                $cost     = $nominal;          // nominal = modal / harga beli
            }
        } elseif ($service->kind === 'rita') {
            // Rita: kasir input jumlah voucher + modal total + harga total. Profit = harga - modal.
            $data = $request->validate([
                'qty'             => 'required|integer|min:1',
                'price'           => 'required|integer|min:1',   // harga total
                'cost_price'      => 'required|integer|min:0',   // modal total
                'payment_method'  => 'required|in:cash,transfer',
                'bank_account_id' => 'nullable|exists:bank_accounts,id',
                'note'            => 'nullable|string|max:500',
            ]);
            $voucherQty = (int) $data['qty'];
            $qty        = 1;                       // 1 baris transaksi (batch)
            $unit       = (int) $data['price'];    // harga total
            $cost       = (int) $data['cost_price']; // modal total
            $subtotal   = $unit;
            $profit     = $unit - $cost;
            $nominal    = $voucherQty;             // simpan jumlah voucher untuk void/stok
        } elseif ($service->kind === 'eceran') {
            // Harga jual & modal diketik kasir; profit = jual - modal.
            $data = $request->validate([
                'price'           => 'required|integer|min:1',
                'cost_price'      => 'required|integer|min:0',
                'payment_method'  => 'required|in:cash,transfer',
                'bank_account_id' => 'nullable|exists:bank_accounts,id',
                'note'            => 'nullable|string|max:500',
            ]);
            $qty      = 1;
            $unit     = (int) $data['price'];
            $cost     = (int) $data['cost_price'];
            $subtotal = $unit;
            $profit   = $unit - $cost;
            $nominal  = null;
        } else {
            $data = $request->validate([
                'qty'             => 'required|integer|min:1',
                'payment_method'  => 'required|in:cash,transfer',
                'bank_account_id' => 'nullable|exists:bank_accounts,id',
                'note'            => 'nullable|string|max:500',
            ]);
            $qty      = (int) $data['qty'];
            $unit     = (int) $service->price;
            $subtotal = $unit * $qty;
            $profit   = ((int) $service->price - (int) $service->cost_price) * $qty;
            $nominal  = null;
            $cost     = (int) $service->cost_price;
        }

        try {
            $transaction = DB::transaction(function () use (
                $service, $branchId, $activeShift, $data, $qty, $unit, $subtotal, $profit, $nominal, $cost, $movesCash
            ) {
                $countToday = Transaction::where('branch_id', $branchId)
                    ->whereDate('created_at', today())->count();
                $trxNumber = 'JSA-' . now()->format('Ymd') . '-' . str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);

                // Default: fee dibayar via metode pilihan (jasa servis / keuangan fee-saja)
                $method        = $data['payment_method'] ?? 'cash';
                $paidCash      = $method === 'cash' ? $subtotal : 0;
                $paidTransfer  = $method === 'transfer' ? $subtotal : 0;
                $bankAccount   = null;

                // Transfer biasa (servis / keuangan fee / eceran): wajib pilih rekening tujuan.
                if (!$movesCash && $method === 'transfer' && $paidTransfer > 0) {
                    if (empty($data['bank_account_id'])) {
                        throw new \RuntimeException('Pilih rekening tujuan transfer (bank / e-wallet).');
                    }
                    $bankAccount = Bank::forBranch($branchId)->active()
                        ->lockForUpdate()->find($data['bank_account_id']);
                    if (!$bankAccount) {
                        throw new \RuntimeException('Rekening tujuan transfer tidak valid atau tidak aktif.');
                    }
                }

                if ($movesCash) {
                    // Jasa keuangan yang menggerakkan nominal (tarik / setor)
                    $bankAccount = Bank::forBranch($branchId)->active()
                        ->lockForUpdate()->find($data['bank_account_id']);
                    if (!$bankAccount) {
                        throw new \RuntimeException('Rekening tidak valid atau tidak aktif.');
                    }

                    $method       = 'cash';
                    $paidTransfer = 0;
                    // Efek ke kas fisik = pergerakan kas bersih (nominal + fee), ditampung di paid_cash
                    if ($service->cash_direction === 'tarik') {
                        $paidCash = $nominal + $subtotal;   // kas naik: terima tunai nominal + fee
                    } else { // setor
                        $paidCash = $subtotal - $nominal;   // kas turun: keluarkan tunai nominal, terima fee
                    }
                }

                // Rita: kunci & validasi saldo deposit + stok produk voucher.
                $ritaService = null;
                $ritaProduct = null;
                if ($service->kind === 'rita') {
                    $ritaService = Service::lockForUpdate()->find($service->id);
                    if ((int) $ritaService->rita_balance < $cost) {
                        throw new \RuntimeException('Saldo Rita tidak cukup untuk modal ini.');
                    }
                    $ritaProduct = Product::forBranch($branchId)->lockForUpdate()->find($ritaService->product_id);
                    if (!$ritaProduct) {
                        throw new \RuntimeException('Produk voucher Rita tidak ditemukan.');
                    }
                    if ($ritaProduct->stock < $nominal) {
                        throw new \RuntimeException("Stok voucher tidak cukup (tersisa {$ritaProduct->stock}).");
                    }
                }

                $transaction = Transaction::create([
                    'branch_id'       => $branchId,
                    'shift_id'        => $activeShift->id,
                    'user_id'         => auth()->id(),
                    'trx_number'      => $trxNumber,
                    'subtotal'        => $subtotal,
                    'discount_type'   => 'none',
                    'discount_value'  => 0,
                    'discount_amount' => 0,
                    'total'           => $subtotal,
                    'payment_method'  => $method,
                    'paid_cash'       => $paidCash,
                    'paid_transfer'   => $paidTransfer,
                    'bank_account_id' => $bankAccount?->id,
                    'change_amount'   => 0,
                    'total_profit'    => $profit,
                    'status'          => 'completed',
                    'note'            => $data['note'] ?? null,
                ]);

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'item_type'      => 'service',
                    'item_id'        => $service->id,
                    'item_name'      => $service->name,
                    'unit_price'     => $unit,
                    'cost_price'     => $cost,
                    'nominal'        => $nominal,
                    'qty'            => $qty,
                    'subtotal'       => $subtotal,
                    'profit'         => $profit,
                ]);

                // Jasa keuangan tarik/setor: gerakkan saldo bank sesuai arah dana (nominal).
                if ($bankAccount && $movesCash) {
                    $mutType = $service->cash_direction === 'tarik' ? 'out' : 'in';
                    $bankAccount->applyMutation($mutType, $nominal, "{$service->name} {$trxNumber}");
                }

                // Transfer biasa: uang masuk ke rekening tujuan sebesar total.
                if ($bankAccount && !$movesCash && $paidTransfer > 0) {
                    $bankAccount->applyMutation('in', $paidTransfer, "{$service->name} {$trxNumber}");
                }

                // Rita: kurangi saldo deposit (sebesar modal) & stok produk voucher (sebanyak qty).
                if ($service->kind === 'rita') {
                    $ritaService->decrement('rita_balance', $cost);

                    $before = $ritaProduct->stock;
                    $after  = $before - $nominal;
                    $ritaProduct->update(['stock' => $after]);
                    StockMovement::create([
                        'branch_id'  => $branchId,
                        'product_id' => $ritaProduct->id,
                        'user_id'    => auth()->id(),
                        'type'       => 'out',
                        'qty_before' => $before,
                        'qty_change' => -$nominal,
                        'qty_after'  => $after,
                        'reference'  => $trxNumber,
                        'note'       => 'Nembak voucher (Rita)',
                    ]);
                }

                $activeShift->recalculate();

                return $transaction;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLog::log('created', $transaction, null, $transaction->toArray());

        return redirect()->route('services.index')
            ->with('success', "Jasa {$service->name} dicatat ({$transaction->trx_number}).");
    }

    private function validateService(Request $request): array
    {
        $base = $request->validate([
            'name'        => 'required|string|max:255',
            'kind'        => 'required|in:servis,keuangan,eceran,rita',
            'category_id' => 'nullable|exists:categories,id',
            'is_active'   => 'boolean',
            'note'        => 'nullable|string|max:500',
        ]);

        if ($base['kind'] === 'rita') {
            // Rita: terikat 1 produk voucher + punya saldo deposit. Harga/modal diketik saat transaksi.
            $branchId = auth()->user()->active_branch_id;
            $extra = $request->validate([
                'product_id'   => [
                    'required',
                    \Illuminate\Validation\Rule::exists('products', 'id')->where('branch_id', $branchId),
                ],
                'rita_balance' => 'required|integer|min:0',
            ]);
            $base['product_id']     = $extra['product_id'];
            $base['rita_balance']   = $extra['rita_balance'];
            $base['price']          = 0;
            $base['cost_price']     = 0;
            $base['default_fee']    = 0;
            $base['cash_direction'] = 'none';
            $base['fee_tiers']      = null;
        } elseif ($base['kind'] === 'eceran') {
            // Harga jual & modal tidak disetel di master; diketik kasir saat transaksi.
            $base['price']          = 0;
            $base['cost_price']     = 0;
            $base['default_fee']    = 0;
            $base['cash_direction'] = 'none';
            $base['fee_tiers']      = null;
        } elseif ($base['kind'] === 'keuangan') {
            $extra = $request->validate([
                'default_fee'      => 'required|integer|min:0',
                'cash_direction'   => 'required|in:none,tarik,setor',
                'fee_tiers'        => 'nullable|array',
                'fee_tiers.*.max'  => 'nullable|integer|min:0',
                'fee_tiers.*.fee'  => 'nullable|integer|min:0',
            ]);
            $base['default_fee']    = $extra['default_fee'];
            $base['cash_direction'] = $extra['cash_direction'];
            $base['fee_tiers']      = $this->normalizeTiers($extra['fee_tiers'] ?? []);
            $base['price']          = 0;
            $base['cost_price']     = 0;
        } else {
            $extra = $request->validate([
                'price'      => 'required|integer|min:0',
                'cost_price' => 'required|integer|min:0',
            ]);
            $base['price']          = $extra['price'];
            $base['cost_price']     = $extra['cost_price'];
            $base['default_fee']    = 0;
            $base['cash_direction'] = 'none';
            $base['fee_tiers']      = null;
        }

        // Field khusus Rita hanya berlaku untuk kind rita.
        if ($base['kind'] !== 'rita') {
            $base['product_id']   = null;
            $base['rita_balance'] = 0;
        }

        return $base;
    }

    /**
     * Bersihkan & urutkan tarif bertingkat: buang baris kosong, urut menaik per batas.
     */
    private function normalizeTiers(array $tiers): ?array
    {
        $clean = collect($tiers)
            ->map(fn ($t) => [
                'max' => isset($t['max']) && $t['max'] !== '' && $t['max'] !== null ? (int) $t['max'] : null,
                'fee' => (int) ($t['fee'] ?? 0),
            ])
            ->filter(fn ($t) => $t['fee'] > 0 || $t['max'] !== null)
            ->sortBy(fn ($t) => $t['max'] ?? PHP_INT_MAX)
            ->values()
            ->all();

        return empty($clean) ? null : $clean;
    }

    private function authorizeBranch(Service $service): void
    {
        if ($service->branch_id !== auth()->user()->active_branch_id) {
            abort(403);
        }
    }
}
