<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'category_id',
        'name',
        'sku',
        'image',
        'price',
        'price_wholesale',
        'cost_price',
        'stock',
        'stock_alert',
        'is_active',
        'note',
    ];

    protected $casts = [
        'price'           => 'integer',
        'price_wholesale' => 'integer',
        'cost_price'      => 'integer',
        'stock'           => 'integer',
        'stock_alert'     => 'integer',
        'is_active'       => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branche::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // ── Scopes ────────────────────────────────────────
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeLowStock($q)
    {
        return $q->where('stock', '>', 0)
                 ->whereColumn('stock', '<=', 'stock_alert');
    }

    public function scopeForBranch($q, $branchId)
    {
        return $q->where('branch_id', $branchId);
    }

    // ── Accessors ─────────────────────────────────────
    public function getIsLowStockAttribute(): bool
    {
        return $this->stock > 0 && $this->stock <= $this->stock_alert;
    }

    public function getIsEmptyAttribute(): bool
    {
        return $this->stock <= 0;
    }

    public function getProfitMarginAttribute(): int
    {
        return $this->price - $this->cost_price;
    }

    public function getStockValueAttribute(): int
    {
        return $this->stock * $this->cost_price;
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /**
     * Buat SKU otomatis & unik per cabang.
     * Format: PREFIX-NNNN (PREFIX dari nama kategori, fallback "PRD").
     */
    public static function generateSku(string $branchId, ?Category $category = null): string
    {
        $prefix = 'PRD';
        if ($category && $category->name) {
            $alpha = strtoupper(preg_replace('/[^A-Za-z]/', '', $category->name));
            if (strlen($alpha) >= 2) {
                $prefix = substr($alpha, 0, 3);
            }
        }

        $seq = static::withTrashed()->where('branch_id', $branchId)->count() + 1;

        do {
            $sku = $prefix . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $seq++;
        } while (static::withTrashed()->where('branch_id', $branchId)->where('sku', $sku)->exists());

        return $sku;
    }
}
