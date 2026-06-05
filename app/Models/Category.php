<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'name',
        'type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branche::class);
    }
    public function products(): HasMany
    {
        return $this->hasMany(Products::class);
    }
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function scopeForBranch($q, $branchId)
    {
        return $q->where('branch_id', $branchId);
    }
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
    public function scopeProduct($q)
    {
        return $q->where('type', 'product');
    }
    public function scopeService($q)
    {
        return $q->where('type', 'service');
    }
}
