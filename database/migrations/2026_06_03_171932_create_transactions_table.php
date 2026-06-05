<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('trx_number')->unique();
            $table->bigInteger('subtotal');
            $table->enum('discount_type', ['none', 'percent', 'nominal'])->default('none');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('total');
            $table->enum('payment_method', ['cash', 'transfer', 'mixed']);
            $table->bigInteger('paid_cash')->default(0);
            $table->bigInteger('paid_transfer')->default(0);
            $table->bigInteger('change_amount')->default(0);
            $table->bigInteger('total_profit')->default(0);
            $table->enum('status', ['completed', 'void', 'return'])->default('completed');
            $table->text('note')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status', 'created_at']);
            $table->index('shift_id');
        });
    }

    public function down(): void { Schema::dropIfExists('transactions'); }
};
