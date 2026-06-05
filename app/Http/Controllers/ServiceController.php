<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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

        return view('dashboard.services.index', compact(
            'services', 'categories', 'servisCount', 'financeCount', 'activeShift'
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

        if ($service->kind === 'keuangan') {
            $data = $request->validate([
                'nominal'        => 'required|integer|min:1',
                'fee'            => 'required|integer|min:0',
                'payment_method' => 'required|in:cash,transfer',
                'note'           => 'nullable|string|max:500',
            ]);
            $qty      = 1;
            $unit     = (int) $data['fee'];
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

        $transaction = DB::transaction(function () use (
            $service, $branchId, $activeShift, $data, $qty, $unit, $subtotal, $profit, $nominal, $cost
        ) {
            $countToday = Transaction::where('branch_id', $branchId)
                ->whereDate('created_at', today())->count();
            $trxNumber = 'JSA-' . now()->format('Ymd') . '-' . str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);

            $method = $data['payment_method'];

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
                'paid_cash'       => $method === 'cash' ? $subtotal : 0,
                'paid_transfer'   => $method === 'transfer' ? $subtotal : 0,
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

            return $transaction;
        });

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
                'default_fee' => 'required|integer|min:0',
            ]);
            $base['default_fee'] = $extra['default_fee'];
            $base['price']       = 0;
            $base['cost_price']  = 0;
        } else {
            $extra = $request->validate([
                'price'      => 'required|integer|min:0',
                'cost_price' => 'required|integer|min:0',
            ]);
            $base['price']       = $extra['price'];
            $base['cost_price']  = $extra['cost_price'];
            $base['default_fee'] = 0;
        }

        return $base;
    }

    private function authorizeBranch(Service $service): void
    {
        if ($service->branch_id !== auth()->user()->active_branch_id) {
            abort(403);
        }
    }
}
