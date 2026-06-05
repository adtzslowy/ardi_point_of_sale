<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'user_id',
        'action',
        'model',
        'model_id',
        'before',
        'after',
        'ip_address',
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    public function branch(): BelongsTo { return $this->belongsTo(Branche::class); }
    public function user(): BelongsTo   { return $this->belongsTo(User::class); }

    public static function log(
        string $action,
        Model $model,
        ?array $before = null,
        ?array $after = null
    ): void {
        static::create([
            'branch_id'  => auth()->user()?->active_branch_id,
            'user_id'    => auth()->id(),
            'action'     => $action,
            'model'      => class_basename($model),
            'model_id'   => $model->getKey(),
            'before'     => $before,
            'after'      => $after,
            'ip_address' => request()->ip(),
        ]);
    }
}
