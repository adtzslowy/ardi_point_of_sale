<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Tambah jenis 'eceran' (Pertalite/BBM, rokok keteng): harga jual & modal
        // diketik kasir saat transaksi. MySQL butuh ALTER ... MODIFY untuk ubah enum.
        DB::statement("ALTER TABLE services MODIFY COLUMN kind ENUM('servis','keuangan','eceran') NOT NULL DEFAULT 'servis'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE services MODIFY COLUMN kind ENUM('servis','keuangan') NOT NULL DEFAULT 'servis'");
    }
};
