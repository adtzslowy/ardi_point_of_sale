<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('opened_by')->constrained('users');
            $table->foreignUuid('closed_by')->nullable()->constrained('users');
            $table->enum('type', ['morning', 'evening'])->default('morning');
            $table->bigInteger('opening_cash')->default(0);
            $table->bigInteger('closing_cash')->nullable();
            $table->bigInteger('total_cash')->default(0);
            $table->bigInteger('total_transfer')->default(0);
            $table->bigInteger('total_sales')->default(0);
            $table->bigInteger('total_profit')->default(0);
            $table->integer('total_transactions')->default(0);
            $table->bigInteger('difference')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('shifts'); }
};
