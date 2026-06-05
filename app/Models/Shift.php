<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'opened_by',
        'closed_by',
        'type',
        'opening_cash',
        'closing_cash',
        'total_cash',
        'total_transfer',
        'total_sales',
        'total_profit',
        'total_transactions',
        'difference',
        'note',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_cash'   => 'integer',
        'closing_cash'   => 'integer',
        'total_cash'     => 'integer',
        'total_transfer' => 'integer',
        'total_sales'    => 'integer',
        'total_profit'   => 'integer',
        'difference'     => 'integer',
        'opened_at'      => 'datetime',
        'closed_at'      => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branche::class);
    }
    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeForBranch($q, $branchId)
    {
        return $q->where('branch_id', $branchId);
    }
    public function scopeOpen($q)
    {
        return $q->where('status', 'open');
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'open';
    }
    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'morning' ? 'Pagi' : 'Malam';
    }

    public function getDurationAttribute(): string
    {
        $end = $this->closed_at ?? now();
        $diff = $this->opened_at->diff($end);
        return $diff->h . 'j ' . $diff->i . 'm';
    }

    public function recalculate(): void
    {
        $q = $this->transactions()->where('status', 'completed');
        $this->update([
            'total_sales'        => $q->sum('total'),
            'total_cash'         => $q->sum('paid_cash'),
            'total_transfer'     => $q->sum('paid_transfer'),
            'total_profit'       => $q->sum('total_profit'),
            'total_transactions' => $q->count(),
        ]);
    }
}
