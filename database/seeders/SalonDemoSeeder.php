<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\Seeder;

class SalonDemoSeeder extends Seeder
{
    public function run(): void
    {
        Customer::firstOrCreate(['phone' => '0800000000'], ['name' => 'ลูกค้าตัวอย่าง']);
        Product::firstOrCreate(['sku' => 'DEMO-SHAMPOO'], ['name' => 'แชมพูบำรุงผม', 'cost_price' => '80.00', 'sell_price' => '150.00', 'min_stock' => 5, 'unit' => 'ขวด', 'is_active' => true]);
        Service::firstOrCreate(['name' => 'ตัดและจัดทรง (ตัวอย่าง)', 'category' => 'cut'], ['price' => '200.00', 'duration_minutes' => 30, 'is_active' => true]);
        Service::firstOrCreate(['name' => 'สระและไดร์ (ตัวอย่าง)', 'category' => 'wash'], ['price' => '120.00', 'duration_minutes' => 20, 'is_active' => true]);
    }
}
