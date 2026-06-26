<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Jenis 'rita' (nembak voucher): punya saldo deposit & terikat 1 produk voucher.
        DB::statement("ALTER TABLE services MODIFY COLUMN kind ENUM('servis','keuangan','eceran','rita') NOT NULL DEFAULT 'servis'");

        Schema::table('services', function (Blueprint $table) {
            $table->bigInteger('rita_balance')->default(0)->after('fee_tiers');
            $table->foreignUuid('product_id')->nullable()->after('category_id')
                  ->constrained('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('rita_balance');
        });

        DB::statement("ALTER TABLE services MODIFY COLUMN kind ENUM('servis','keuangan','eceran') NOT NULL DEFAULT 'servis'");
    }
};
