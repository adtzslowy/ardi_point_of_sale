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
        'kind',
        'name',
        'price',
        'cost_price',
        'default_fee',
        'is_active',
        'note',
    ];

    protected $casts = [
        'price'       => 'integer',
        'cost_price'  => 'integer',
        'default_fee' => 'integer',
        'is_active'   => 'boolean',
    ];

    public function branch(): BelongsTo   { return $this->belongsTo(Branche::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }

    public function scopeActive($q)               { return $q->where('is_active', true); }
    public function scopeForBranch($q, $branchId) { return $q->where('branch_id', $branchId); }
    public function scopeKind($q, $kind)          { return $q->where('kind', $kind); }
}
