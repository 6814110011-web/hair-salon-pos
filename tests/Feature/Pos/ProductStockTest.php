<?php

namespace Tests\Feature\Pos;

use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\PosFixtures;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    use PosFixtures, RefreshDatabase;

    public function test_product_create_ignores_stock_injection_and_validates_sku(): void
    {
        $input = ['sku' => 'ONE', 'name' => 'New', 'cost_price' => '50.00', 'sell_price' => '100.00', 'min_stock' => 5, 'unit' => 'ชิ้น', 'is_active' => 1, 'stock_qty' => 999];
        $this->actingAs($this->admin())->post('/products', $input)->assertRedirect();
        $this->assertDatabaseHas('products', ['sku' => 'ONE', 'stock_qty' => 0]);
        $this->post('/products', $input)->assertSessionHasErrors('sku');
    }

    public function test_stock_changes_are_recorded_and_locked_to_admins(): void
    {
        $admin = $this->admin();
        $product = $this->product(['stock_qty' => 0]);
        app(StockService::class)->receive($admin, $product, 5, 'Receive');
        app(StockService::class)->adjust($admin, $product, 3, 'Correction');
        $this->assertSame(3, $product->fresh()->stock_qty);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'adjust', 'qty' => -2, 'stock_after' => 3]);
        $this->actingAs($this->cashier())->post('/products/'.$product->id.'/stock', ['mode' => 'receive', 'quantity' => 4, 'reason' => 'No'])->assertForbidden();
    }

    public function test_stock_overflow_fails_without_a_ledger_write(): void
    {
        $admin = $this->admin();
        $product = $this->product(['stock_qty' => 2147483647]);
        try {
            app(StockService::class)->receive($admin, $product, 1, 'Overflow');
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }
        $this->assertSame(2147483647, $product->fresh()->stock_qty);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_cashier_cannot_see_cost_and_low_stock_filter_works(): void
    {
        $product = $this->product(['cost_price' => '9876.54', 'stock_qty' => 2, 'min_stock' => 5]);
        $this->product(['name' => 'Plenty', 'stock_qty' => 99]);
        $this->actingAs($this->cashier())->get('/products?low_stock=1')->assertOk()->assertSee($product->name)->assertDontSee('9876.54')->assertDontSee('Plenty');
        $this->get('/products/'.$product->id)->assertForbidden();
    }

    public function test_stock_forms_reject_invalid_changes_and_show_history(): void
    {
        $product = $this->product();
        $this->actingAs($this->admin())->post('/products/'.$product->id.'/stock', ['mode' => 'adjust', 'target_quantity' => -1, 'reason' => ''])->assertSessionHasErrors(['target_quantity', 'reason']);
        $this->get('/products/'.$product->id)->assertOk()->assertSee($product->sku);
    }
}
