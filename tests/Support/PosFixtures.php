<?php

namespace Tests\Support;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;

trait PosFixtures
{
    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function cashier(): User
    {
        return User::factory()->create(['role' => 'cashier']);
    }

    protected function stylist(): User
    {
        return User::factory()->create(['role' => 'stylist']);
    }

    protected function customer(array $attributes = []): Customer
    {
        return Customer::forceCreate(array_merge(['name' => 'Customer', 'phone' => '08'.random_int(10000000, 99999999)], $attributes));
    }

    protected function product(array $attributes = []): Product
    {
        return Product::forceCreate(array_merge(['name' => 'Shampoo', 'sku' => 'P'.bin2hex(random_bytes(4)), 'sell_price' => '100.00', 'stock_qty' => 5], $attributes));
    }

    protected function service(array $attributes = []): Service
    {
        return Service::forceCreate(array_merge(['name' => 'Haircut', 'category' => 'cut', 'price' => '150.00'], $attributes));
    }

    protected function promotion(array $attributes = []): Promotion
    {
        return Promotion::forceCreate(array_merge(['name' => 'Five visits', 'visit_required' => 5, 'reward_type' => 'amount', 'reward_value' => '50.00'], $attributes));
    }
}
