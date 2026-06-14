<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionReturnItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'transaction_return_id',
        'transaction_item_id',
        'product_id',
        'item_name',
        'unit_price',
        'qty',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'qty'        => 'integer',
        'subtotal'   => 'integer',
    ];

    public function return(): BelongsTo
    {
        return $this->belongsTo(TransactionReturn::class, 'transaction_return_id');
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(TransactionItem::class, 'transaction_item_id');
    }
}
