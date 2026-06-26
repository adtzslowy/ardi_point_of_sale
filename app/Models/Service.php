<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'category_id',
        'product_id',
        'kind',
        'name',
        'price',
        'cost_price',
        'default_fee',
        'cash_direction',
        'fee_tiers',
        'rita_balance',
        'is_active',
        'note',
    ];

    protected $casts = [
        'price'        => 'integer',
        'cost_price'   => 'integer',
        'default_fee'  => 'integer',
        'fee_tiers'    => 'array',
        'rita_balance' => 'integer',
        'is_active'    => 'boolean',
    ];

    /**
     * Hitung biaya admin otomatis untuk sebuah nominal berdasarkan tarif bertingkat.
     * Jatuh kembali ke default_fee bila belum ada tarif bertingkat.
     */
    public function feeForNominal(int $nominal): int
    {
        $tiers = collect($this->fee_tiers ?? [])
            ->map(fn ($t) => [
                'max' => isset($t['max']) && $t['max'] !== '' && $t['max'] !== null ? (int) $t['max'] : null,
                'fee' => (int) ($t['fee'] ?? 0),
            ])
            ->sortBy(fn ($t) => $t['max'] ?? PHP_INT_MAX)
            ->values();

        if ($tiers->isEmpty()) {
            return (int) $this->default_fee;
        }

        foreach ($tiers as $tier) {
            if ($tier['max'] === null || $nominal <= $tier['max']) {
                return $tier['fee'];
            }
        }

        return $tiers->last()['fee'];
    }

    public function getKindLabelAttribute(): string
    {
        return match ($this->kind) {
            'keuangan' => 'Keuangan',
            'eceran'   => 'Eceran',
            'rita'     => 'Rita',
            default    => 'Servis',
        };
    }

    public function getCashDirectionLabelAttribute(): string
    {
        return match ($this->cash_direction) {
            'tarik' => 'Tarik tunai (bank −, kas +)',
            'setor' => 'Setor/transfer (bank +, kas −)',
            default => 'Fee saja',
        };
    }

    public function branch(): BelongsTo   { return $this->belongsTo(Branche::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }

    public function scopeActive($q)               { return $q->where('is_active', true); }
    public function scopeForBranch($q, $branchId) { return $q->where('branch_id', $branchId); }
    public function scopeKind($q, $kind)          { return $q->where('kind', $kind); }
}
