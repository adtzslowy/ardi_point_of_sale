<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->active_branch_id;

        $products = Product::with('category')
            ->forBranch($branchId)
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%")
            )
            ->when($request->category, fn($q) =>
                $q->where('category_id', $request->category)
            )
            ->when($request->filter === 'low_stock', fn($q) => $q->lowStock())
            ->when($request->filter === 'empty', fn($q) => $q->where('stock', 0))
            ->when($request->status === 'inactive', fn($q) => $q->where('is_active', false))
            ->when(!$request->status || $request->status === 'active', fn($q) => $q->active())
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $categories    = Category::forBranch($branchId)->product()->active()->orderBy('name')->get();
        $lowStockCount = Product::forBranch($branchId)->active()->lowStock()->count();
        $emptyCount    = Product::forBranch($branchId)->active()->where('stock', 0)->count();
        $totalValue    = Product::forBranch($branchId)->active()->get()
            ->sum(fn($p) => $p->stock * $p->cost_price);

        return view('dashboard.products.index', compact(
            'products', 'categories', 'lowStockCount', 'emptyCount', 'totalValue'
        ));
    }

    public function create()
    {
        $branchId   = auth()->user()->active_branch_id;
        $categories = Category::forBranch($branchId)->product()->active()->orderBy('name')->get();
        return view('dashboard.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'sku'             => 'nullable|string|max:50',
            'category_id'     => 'nullable|exists:categories,id',
            'price'           => 'required|integer|min:0',
            'price_wholesale' => 'required|integer|min:0',
            'cost_price'      => 'required|integer|min:0',
            'stock'           => 'required|integer|min:0',
            'stock_alert'     => 'required|integer|min:0',
            'note'            => 'nullable|string|max:500',
        ]);

        $data['branch_id'] = auth()->user()->active_branch_id;
        $product           = Product::create($data);

        if ($data['stock'] > 0) {
            StockMovement::create([
                'branch_id'  => $data['branch_id'],
                'product_id' => $product->id,
                'user_id'    => auth()->id(),
                'type'       => 'in',
                'qty_before' => 0,
                'qty_change' => $data['stock'],
                'qty_after'  => $data['stock'],
                'note'       => 'Stok awal saat produk dibuat',
            ]);
        }

        ActivityLog::log('created', $product, null, $product->toArray());

        return redirect()->route('products.index')
            ->with('success', "Produk {$product->name} berhasil ditambahkan.");
    }

    public function show(Product $product)
    {
        $this->authorizeBranch($product);
        $product->load(['category', 'stockMovements' => fn($q) => $q->latest()->limit(20)]);
        $product->stockMovements->load('user');
        return view('dashboard.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $this->authorizeBranch($product);
        $categories = Category::forBranch($product->branch_id)->product()->active()->orderBy('name')->get();
        return view('dashboard.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeBranch($product);

        $before = $product->toArray();

        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'sku'             => 'nullable|string|max:50',
            'category_id'     => 'nullable|exists:categories,id',
            'price'           => 'required|integer|min:0',
            'price_wholesale' => 'required|integer|min:0',
            'cost_price'      => 'required|integer|min:0',
            'stock_alert'     => 'required|integer|min:0',
            'is_active'       => 'boolean',
            'note'            => 'nullable|string|max:500',
        ]);

        $product->update($data);
        ActivityLog::log('updated', $product, $before, $product->fresh()->toArray());

        return redirect()->route('products.index')
            ->with('success', "Produk {$product->name} berhasil diupdate.");
    }

    public function adjustStock(Request $request, Product $product)
    {
        $this->authorizeBranch($product);

        $request->validate([
            'type'   => 'required|in:in,adjustment,owner_take',
            'qty'    => 'required|integer|min:1',
            'note'   => 'required|string|max:500',
        ]);

        $before = $product->stock;
        $change = $request->type === 'in' ? $request->qty : -$request->qty;
        $after  = $before + $change;

        if ($after < 0) {
            return back()->with('error', 'Stok tidak boleh kurang dari 0.');
        }

        $product->update(['stock' => $after]);

        StockMovement::create([
            'branch_id'  => $product->branch_id,
            'product_id' => $product->id,
            'user_id'    => auth()->id(),
            'type'       => $request->type,
            'qty_before' => $before,
            'qty_change' => $change,
            'qty_after'  => $after,
            'note'       => $request->note,
        ]);

        ActivityLog::log('stock_adjusted', $product,
            ['stock' => $before],
            ['stock' => $after]
        );

        return back()->with('success', 'Stok berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $this->authorizeBranch($product);
        ActivityLog::log('deleted', $product, $product->toArray());
        $product->delete();
        return redirect()->route('products.index')
            ->with('success', "Produk {$product->name} berhasil dihapus.");
    }

    private function authorizeBranch(Product $product): void
    {
        if ($product->branch_id !== auth()->user()->active_branch_id) {
            abort(403);
        }
    }
}
