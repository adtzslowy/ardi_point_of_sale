<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $branchId = auth()->user()->active_branch_id;
        $type     = in_array($request->type, ['product', 'service']) ? $request->type : 'product';

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'name')->where(
                    fn ($q) => $q->where('branch_id', $branchId)->where('type', $type)
                ),
            ],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique'   => 'Kategori dengan nama ini sudah ada di cabang ini.',
        ]);

        $category = Category::create([
            'branch_id' => $branchId,
            'name'      => $data['name'],
            'type'      => $type,
            'is_active' => true,
        ]);

        ActivityLog::log('created', $category, null, $category->toArray());

        if ($request->wantsJson()) {
            return response()->json([
                'id'   => $category->id,
                'name' => $category->name,
            ], 201);
        }

        return back()->with('success', "Kategori {$category->name} berhasil ditambahkan.");
    }
}
