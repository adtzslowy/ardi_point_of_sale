<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Arah arus dana untuk jasa keuangan:
            //  none  = hanya fee, tidak menggerakkan nominal
            //  tarik = tarik tunai  -> saldo bank turun, kas fisik naik
            //  setor = setor/transfer -> saldo bank naik, kas fisik turun
            $table->enum('cash_direction', ['none', 'tarik', 'setor'])->default('none')->after('default_fee');

            // Tarif admin bertingkat per nominal: [{ "max": 500000, "fee": 5000 }, { "max": null, "fee": 10000 }]
            $table->json('fee_tiers')->nullable()->after('cash_direction');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['cash_direction', 'fee_tiers']);
        });
    }
};
