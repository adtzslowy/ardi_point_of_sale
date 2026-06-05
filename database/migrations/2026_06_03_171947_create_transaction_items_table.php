<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->enum('item_type', ['product', 'service']);
            $table->uuid('item_id');
            $table->string('item_name');
            $table->bigInteger('unit_price');
            $table->bigInteger('cost_price');
            $table->integer('qty');
            $table->bigInteger('subtotal');
            $table->bigInteger('profit');
            $table->timestamps();

            $table->index('transaction_id');
            $table->index(['item_type', 'item_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('transaction_items'); }
};
