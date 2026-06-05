<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->enum('kind', ['servis', 'keuangan'])->default('servis')->after('category_id');
            $table->bigInteger('default_fee')->default(0)->after('cost_price');
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->bigInteger('nominal')->nullable()->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['kind', 'default_fee']);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn('nominal');
        });
    }
};
