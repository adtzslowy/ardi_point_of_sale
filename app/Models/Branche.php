<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branche extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'address', 'phone', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function users(): HasMany        { return $this->hasMany(User::class); }
    public function products(): HasMany     { return $this->hasMany(Product::class); }
    public function services(): HasMany     { return $this->hasMany(Service::class); }
    public function shifts(): HasMany       { return $this->hasMany(Shift::class); }
    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
    public function bankAccounts(): HasMany { return $this->hasMany(Bank::class); }
    public function categories(): HasMany   { return $this->hasMany(Category::class); }

    public function activeShift()
    {
        return $this->hasMany(Shift::class)
            ->where('status', 'open')
            ->latest()
            ->first();
    }
}
