<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use HasUuids;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'branch_id',
        'type',
        'bank_name',
        'account_number',
        'account_name',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'balance'   => 'integer',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branche::class, 'branch_id');
    }

    public function mutations(): HasMany
    {
        return $this->hasMany(BankMutation::class, 'bank_account_id');
    }

    public function scopeForBranch($q, $branchId)
    {
        return $q->where('branch_id', $branchId);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'ewallet' ? 'E-Wallet' : 'Bank';
    }

    public function getLabelAttribute(): string
    {
        return trim("{$this->bank_name} · {$this->account_number}");
    }

    /**
     * Catat mutasi & sesuaikan saldo rekening.
     */
    public function applyMutation(string $type, int $amount, ?string $description = null): BankMutation
    {
        $before = (int) $this->balance;
        $after  = $type === 'in' ? $before + $amount : $before - $amount;

        $this->update(['balance' => $after]);

        return $this->mutations()->create([
            'user_id'        => auth()->id(),
            'type'           => $type,
            'amount'         => $amount,
            'balance_before' => $before,
            'balance_after'  => $after,
            'description'    => $description,
        ]);
    }
}
