<?php

  namespace App\Models;

  use Illuminate\Database\Eloquent\Concerns\HasUuids;
  use Illuminate\Database\Eloquent\Model;
  use Illuminate\Database\Eloquent\Relations\BelongsTo;

  class TransactionItem extends Model
  {
      use HasUuids;

      protected $fillable = [
          'transaction_id', 'item_type', 'item_id', 'item_name',
          'unit_price', 'cost_price', 'nominal', 'qty', 'subtotal', 'profit',
      ];

      protected $casts = [
          'unit_price' => 'integer',
          'cost_price' => 'integer',
          'nominal'    => 'integer',
          'qty'        => 'integer',
          'subtotal'   => 'integer',
          'profit'     => 'integer',
      ];

      public function transaction(): BelongsTo
      {
          return $this->belongsTo(Transaction::class);
      }
  }
