<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionReturn extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'transaction_id',
        'user_id',
        'return_number',
        'total_refund',
        'refund_method',
        'bank_account_id',
        'reason',
    ];

    protected $casts = [
        'total_refund' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionReturnItem::class);
    }
}
