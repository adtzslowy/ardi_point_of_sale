<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'product_id',
        'user_id',
        'type',
        'qty_before',
        'qty_change',
        'qty_after',
        'reference',
        'note',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branche::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
