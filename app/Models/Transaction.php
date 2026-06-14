<?php

  namespace App\Models;

  use Illuminate\Database\Eloquent\Concerns\HasUuids;
  use Illuminate\Database\Eloquent\Model;
  use Illuminate\Database\Eloquent\Relations\BelongsTo;
  use Illuminate\Database\Eloquent\Relations\HasMany;
  use Illuminate\Database\Eloquent\SoftDeletes;

  class Transaction extends Model
  {
      use HasUuids, SoftDeletes;

      protected $fillable = [
          'branch_id', 'shift_id', 'user_id', 'trx_number',
          'subtotal', 'discount_type', 'discount_value', 'discount_amount', 'total',
          'payment_method', 'paid_cash', 'paid_transfer', 'bank_account_id', 'change_amount',
          'total_profit', 'status', 'note', 'void_reason',
      ];

      protected $casts = [
          'subtotal'        => 'integer',
          'discount_value'  => 'decimal:2',
          'discount_amount' => 'integer',
          'total'           => 'integer',
          'paid_cash'       => 'integer',
          'paid_transfer'   => 'integer',
          'change_amount'   => 'integer',
          'total_profit'    => 'integer',
      ];

      public function branch(): BelongsTo
      {
          return $this->belongsTo(Branche::class);
      }

      public function shift(): BelongsTo
      {
          return $this->belongsTo(Shift::class);
      }

      public function kasir(): BelongsTo
      {
          return $this->belongsTo(User::class, 'user_id');
      }

      public function items(): HasMany
      {
          return $this->hasMany(TransactionItem::class);
      }

      public function bankAccount(): BelongsTo
      {
          return $this->belongsTo(Bank::class, 'bank_account_id');
      }

      public function returns(): HasMany
      {
          return $this->hasMany(TransactionReturn::class);
      }

      public function scopeForBranch($q, $branchId)
      {
          return $q->where('branch_id', $branchId);
      }

      public function scopeCompleted($q)
      {
          return $q->where('status', 'completed');
      }

      public function getPaymentLabelAttribute(): string
      {
          return match ($this->payment_method) {
              'cash'     => 'Tunai',
              'transfer' => 'Transfer',
              'mixed'    => 'Campuran',
              default    => $this->payment_method,
          };
      }
  }
