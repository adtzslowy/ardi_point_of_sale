<?php

namespace Database\Seeders;

use App\Models\Branche;
use App\Models\Category;
use App\Models\Product;
use App\Models\Products;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'view dashboard',
            'view transaction',
            'create transaction',
            'void transaction',
            'return transaction',
            'view product',
            'create product',
            'update product',
            'delete product',
            'adjust stock',
            'owner take stock',
            'view service',
            'create service',
            'update service',
            'delete service',
            'view shift',
            'open shift',
            'close shift',
            'view bank',
            'manage bank',
            'view report',
            'export report',
            'view employee',
            'manage employee',
            'view settings',
            'manage settings',
            'view activity log',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles
        $owner   = Role::firstOrCreate(['name' => 'owner']);
        $kasir   = Role::firstOrCreate(['name' => 'kasir']);

        $owner->givePermissionTo(Permission::all());



        $kasir->givePermissionTo([
            'view dashboard',
            'view transaction',
            'create transaction',
            'view product',
            'view service',
            'view shift',
            'open shift',
            'close shift',
        ]);


        // Branches
        $utama   = Branche::firstOrCreate(['name' => 'Cabang Utama'],   ['address' => 'Jl. Merdeka No. 1',  'phone' => '0811-1111-1111']);
        $cabang2 = Branche::firstOrCreate(['name' => 'Cabang 2'],       ['address' => 'Jl. Sudirman No. 2', 'phone' => '0822-2222-2222']);

        // Users
        User::firstOrCreate(['email' => 'owner@ardi.id'], [
            'branch_id' => $utama->id,
            'user_id'   => 'owner',
            'name'      => 'Ardi Rahman',
            'password'  => Hash::make('password'),
        ])->syncRoles('owner');

        User::firstOrCreate(['email' => 'kasir@ardi.id'], [
            'branch_id' => $utama->id,

            'user_id'   => 'kasir1',
            'name'      => 'Rini Kasir',
            'password'  => Hash::make('password'),
        ])->syncRoles('kasir');

        User::firstOrCreate(['email' => 'kasir2@ardi.id'], [
            'branch_id' => $cabang2->id,

            'user_id'   => 'kasir2',
            'name'      => 'Sari Kasir',
            'password'  => Hash::make('password'),
        ])->syncRoles('kasir');

        // Categories Cabang Utama
        $catHP    = Category::firstOrCreate(['name' => 'Handphone',  'branch_id' => $utama->id], ['type' => 'product', 'sort_order' => 1]);
        $catAk    = Category::firstOrCreate(['name' => 'Aksesoris',  'branch_id' => $utama->id], ['type' => 'product', 'sort_order' => 2]);
        $catSerHP = Category::firstOrCreate(['name' => 'Servis HP',  'branch_id' => $utama->id], ['type' => 'service', 'sort_order' => 1]);
        $catSoft  = Category::firstOrCreate(['name' => 'Software',   'branch_id' => $utama->id], ['type' => 'service', 'sort_order' => 2]);

        // Products
        $products = [
            ['name' => 'Samsung Galaxy A15 5G', 'sku' => 'HP-001', 'category_id' => $catHP->id,  'price' => 2199000, 'price_wholesale' => 2050000, 'cost_price' => 1850000, 'stock' => 8,  'stock_alert' => 3],
            ['name' => 'iPhone 13 128GB',        'sku' => 'HP-002', 'category_id' => $catHP->id,  'price' => 8500000, 'price_wholesale' => 8200000, 'cost_price' => 7200000, 'stock' => 3,  'stock_alert' => 2],
            ['name' => 'Charger GaN 65W',        'sku' => 'AK-001', 'category_id' => $catAk->id,  'price' => 189000,  'price_wholesale' => 160000,  'cost_price' => 120000,  'stock' => 15, 'stock_alert' => 5],
            ['name' => 'Tempered Glass',         'sku' => 'AK-002', 'category_id' => $catAk->id,  'price' => 35000,   'price_wholesale' => 28000,   'cost_price' => 15000,   'stock' => 0,  'stock_alert' => 10],
            ['name' => 'Earphone Bluetooth TWS', 'sku' => 'AK-003', 'category_id' => $catAk->id,  'price' => 129000,  'price_wholesale' => 110000,  'cost_price' => 80000,   'stock' => 6,  'stock_alert' => 3],
        ];
        foreach ($products as $p) {
            Product::firstOrCreate(
                ['sku' => $p['sku'], 'branch_id' => $utama->id],
                array_merge($p, ['branch_id' => $utama->id])
            );
        }

        // Services
        $services = [
            ['name' => 'Ganti LCD Samsung',  'category_id' => $catSerHP->id, 'price' => 450000, 'cost_price' => 280000],
            ['name' => 'Ganti Baterai',      'category_id' => $catSerHP->id, 'price' => 150000, 'cost_price' => 80000],
            ['name' => 'Servis Speaker',     'category_id' => $catSerHP->id, 'price' => 100000, 'cost_price' => 50000],
            ['name' => 'Flashing / Reset',   'category_id' => $catSoft->id,  'price' => 75000,  'cost_price' => 10000],
        ];
        foreach ($services as $s) {
            Service::firstOrCreate(
                ['name' => $s['name'], 'branch_id' => $utama->id],
                array_merge($s, ['branch_id' => $utama->id])
            );
        }
    }
}
