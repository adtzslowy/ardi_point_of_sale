<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Jumlah qty per item yang sudah diretur (untuk retur sebagian)
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->integer('returned_qty')->default(0)->after('qty');
        });

        // Header retur
        Schema::create('transaction_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('return_number')->unique();
            $table->bigInteger('total_refund')->default(0);
            $table->enum('refund_method', ['cash', 'transfer'])->default('cash');
            $table->foreignUuid('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'created_at']);
        });

        // Detail item yang diretur
        Schema::create('transaction_return_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_return_id')->constrained('transaction_returns')->cascadeOnDelete();
            $table->foreignUuid('transaction_item_id')->constrained('transaction_items')->cascadeOnDelete();
            $table->uuid('product_id')->nullable();
            $table->string('item_name');
            $table->bigInteger('unit_price');
            $table->integer('qty');
            $table->bigInteger('subtotal');
            $table->timestamps();

            $table->index('transaction_return_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_return_items');
        Schema::dropIfExists('transaction_returns');
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn('returned_qty');
        });
    }
};
