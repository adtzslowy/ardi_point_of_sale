<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bank;
use App\Models\Category;
use App\Models\Service;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->active_branch_id;

        $services = Service::with('category')
            ->forBranch($branchId)
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
            )
            ->when(in_array($request->kind, ['servis', 'keuangan']), fn($q) =>
                $q->kind($request->kind)
            )
            ->when($request->category, fn($q) =>
                $q->where('category_id', $request->category)
            )
            ->when($request->status === 'inactive', fn($q) => $q->where('is_active', false))
            ->when(!$request->status || $request->status === 'active', fn($q) => $q->active())
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $categories  = Category::forBranch($branchId)->service()->active()->orderBy('name')->get();
        $servisCount = Service::forBranch($branchId)->active()->kind('servis')->count();
        $financeCount = Service::forBranch($branchId)->active()->kind('keuangan')->count();
        $activeShift = Shift::forBranch($branchId)->open()->latest()->first();

        $bankAccounts = Bank::forBranch($branchId)->active()
            ->orderBy('type')->orderBy('bank_name')->get()
            ->map(fn ($b) => [
                'id'         => $b->id,
                'type_label' => $b->type_label,
                'bank_name'  => $b->bank_name,
                'account_number' => $b->account_number,
            ]);

        return view('dashboard.services.index', compact(
            'services', 'categories', 'servisCount', 'financeCount', 'activeShift', 'bankAccounts'
        ));
    }

    public function create()
    {
        $branchId   = auth()->user()->active_branch_id;
        $categories = Category::forBranch($branchId)->service()->active()->orderBy('name')->get();
        return view('dashboard.services.create', compact('categories'));
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
        return view('dashboard.services.edit', compact('service', 'categories'));
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
            }
            $data = $request->validate($rules);

            $qty      = 1;
            $unit     = (int) $data['fee'];      // fee = pendapatan konter
            $subtotal = $unit;
            $profit   = $unit;
            $nominal  = (int) $data['nominal'];
            $cost     = 0;
        } else {
            $data = $request->validate([
                'qty'            => 'required|integer|min:1',
                'payment_method' => 'required|in:cash,transfer',
                'note'           => 'nullable|string|max:500',
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

                // Gerakkan saldo bank sesuai arah dana
                if ($bankAccount) {
                    $mutType = $service->cash_direction === 'tarik' ? 'out' : 'in';
                    $bankAccount->applyMutation($mutType, $nominal, "{$service->name} {$trxNumber}");
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
            'kind'        => 'required|in:servis,keuangan',
            'category_id' => 'nullable|exists:categories,id',
            'is_active'   => 'boolean',
            'note'        => 'nullable|string|max:500',
        ]);

        if ($base['kind'] === 'keuangan') {
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
