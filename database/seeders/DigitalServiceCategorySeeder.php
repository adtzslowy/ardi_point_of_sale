<?php

namespace Database\Seeders;

use App\Models\Branche;
use App\Models\Category;
use Illuminate\Database\Seeder;

class DigitalServiceCategorySeeder extends Seeder
{
    /**
     * Kategori layanan digital konter (PPOB, Bank, E-Wallet, dll).
     * Item di tiap kategori diisi sendiri oleh owner lewat menu Layanan & Jasa.
     */
    public function run(): void
    {
        $categories = [
            'PPOB'      => 11,  // Pulsa, paket data, voucher PLN, BPJS, diamond, dll
            'Bank'      => 12,  // Transfer, tarik tunai, cicilan, VA bank
            'E-Wallet'  => 13,  // Top up, tarik tunai
            'Pertalite' => 14,  // BBM
            'Rita'      => 15,  // Nembak voucher
        ];

        $branches = Branche::all();

        foreach ($branches as $branch) {
            foreach ($categories as $name => $sortOrder) {
                Category::firstOrCreate(
                    ['name' => $name, 'branch_id' => $branch->id, 'type' => 'service'],
                    ['sort_order' => $sortOrder, 'is_active' => true],
                );
            }
        }
    }
}
