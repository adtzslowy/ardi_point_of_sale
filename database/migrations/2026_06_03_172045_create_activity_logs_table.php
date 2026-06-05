<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('action');
            $table->string('model');
            $table->uuid('model_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'created_at']);
            $table->index(['model', 'model_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('activity_logs'); }
};
