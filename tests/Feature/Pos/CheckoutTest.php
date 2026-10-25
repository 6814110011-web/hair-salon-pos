<?php

namespace Tests\Feature\Pos;

use App\Models\Product;
use App\Services\SaleCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\PosFixtures;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use PosFixtures, RefreshDatabase;

    private function input(Product $product, array $changes = []): array
    {
        return array_replace(['checkout_token' => (string) Str::uuid(), 'method' => 'cash', 'received' => '500.00', 'items' => [['type' => 'product', 'id' => $product->id, 'qty' => 2, 'expected_price' => $product->sell_price]]], $changes);
    }

    public function test_mixed_sale_snapshots_prices_counts_one_visit_and_takes_stock(): void
    {
        $cashier = $this->cashier();
        $product = $this->product();
        $service = $this->service();
        $stylist = $this->stylist();
        $customer = $this->customer();
        $data = $this->input($product, ['customer_id' => $customer->id]);
        $data['items'][] = ['type' => 'service', 'id' => $service->id, 'qty' => 1, 'stylist_id' => $stylist->id, 'expected_price' => '150.00'];
        $sale = app(SaleCheckoutService::class)->checkout($cashier, $data);
        $this->assertSame('350.00', $sale->total);
        $this->assertSame('150.00', $sale->payments()->first()->change);
        $this->assertSame(3, $product->fresh()->stock_qty);
        $this->assertSame(1, $customer->fresh()->total_visits);
        $this->assertDatabaseHas('stock_movements', ['sale_id' => $sale->id, 'qty' => 2, 'type' => 'out']);
        $product->update(['name' => 'Changed', 'sell_price' => '999.00']);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'item_name' => 'Shampoo', 'price' => 100]);
    }

    public function test_same_checkout_token_does_not_sell_twice(): void
    {
        $cashier = $this->cashier();
        $product = $this->product();
        $data = $this->input($product);
        $first = app(SaleCheckoutService::class)->checkout($cashier, $data);
        $second = app(SaleCheckoutService::class)->checkout($cashier, $data);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(3, $product->fresh()->stock_qty);
        $this->assertNull($first->customer_id);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->expectException(HttpException::class);
        app(SaleCheckoutService::class)->checkout($this->cashier(), $data);
    }

    public function test_client_totals_are_ignored_and_integer_cents_are_exact(): void
    {
        $product = $this->product(['sell_price' => '0.10']);
        $sale = app(SaleCheckoutService::class)->checkout($this->cashier(), $this->input($product, ['total' => '0.01', 'discount' => 999, 'user_id' => 999, 'received' => '0.20']));
        $this->assertSame('0.20', $sale->total);
        $this->assertSame('0.00', $sale->discount);
    }

    public function test_insufficient_cash_or_stock_and_stale_prices_roll_back(): void
    {
        $cashier = $this->cashier();
        $product = $this->product();
        $cases = [$this->input($product, ['received' => '1.00']), $this->input($product, ['items' => [['type' => 'product', 'id' => $product->id, 'qty' => 6, 'expected_price' => '100.00']]]), $this->input($product, ['items' => [['type' => 'product', 'id' => $product->id, 'qty' => 1, 'expected_price' => '0.01']]])];
        foreach ($cases as $data) {
            try {
                app(SaleCheckoutService::class)->checkout($cashier, $data);
                $this->fail('Invalid checkout accepted');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(5, $product->fresh()->stock_qty);
    }

    public function test_combined_duplicate_quantities_are_checked_before_stock_deduction(): void
    {
        $product = $this->product(['stock_qty' => 1000]);
        $cashier = $this->cashier();
        $row = ['type' => 'product', 'id' => $product->id, 'qty' => 500, 'expected_price' => '100.00'];
        $this->expectException(ValidationException::class);
        app(SaleCheckoutService::class)->checkout($cashier, $this->input($product, ['received' => '100000.00', 'items' => [$row, $row]]));
    }

    public function test_aggregate_money_overflow_is_rejected(): void
    {
        $product = $this->product(['sell_price' => '99999999.99']);
        $this->expectException(ValidationException::class);
        app(SaleCheckoutService::class)->checkout($this->cashier(), $this->input($product, ['received' => '99999999.99']));
    }

    public function test_service_needs_an_active_stylist_and_noncash_needs_a_reference(): void
    {
        $product = $this->product();
        $service = $this->service();
        $cashier = $this->cashier();
        foreach ([['method' => 'card'], ['items' => [['type' => 'service', 'id' => $service->id, 'qty' => 1, 'expected_price' => '150.00']]]] as $changes) {
            try {
                app(SaleCheckoutService::class)->checkout($cashier, $this->input($product, $changes));
                $this->fail();
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_promotion_qualifies_on_current_visit_and_duplicate_reward_rolls_back(): void
    {
        $customer = $this->customer(['total_visits' => 4]);
        $promotion = $this->promotion();
        $product = $this->product(['stock_qty' => 10]);
        $cashier = $this->cashier();
        $attributes = ['customer_id' => $customer->id, 'promotion_id' => $promotion->id];
        $first = app(SaleCheckoutService::class)->checkout($cashier, $this->input($product, $attributes));
        $this->assertSame('150.00', $first->total);
        $this->assertSame(5, $customer->fresh()->total_visits);
        try {
            app(SaleCheckoutService::class)->checkout($cashier, $this->input($product, $attributes));
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('promotion', $e->errors());
        }
        $this->assertSame(8, $product->fresh()->stock_qty);
        $this->assertSame(5, $customer->fresh()->total_visits);
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_percentage_promotion_rounds_half_up_and_noncash_has_no_change(): void
    {
        $customer = $this->customer(['total_visits' => 4]);
        $promotion = $this->promotion(['reward_type' => 'percent', 'reward_value' => '50.00']);
        $product = $this->product(['sell_price' => '0.05']);
        $data = $this->input($product, ['customer_id' => $customer->id, 'promotion_id' => $promotion->id, 'method' => 'transfer', 'ref_no' => 'T1', 'items' => [['type' => 'product', 'id' => $product->id, 'qty' => 1, 'expected_price' => '0.05']]]);
        $sale = app(SaleCheckoutService::class)->checkout($this->cashier(), $data);
        $this->assertSame('0.03', $sale->discount);
        $this->assertSame('0.02', $sale->total);
        $this->assertSame('0.00', $sale->payments()->first()->change);
    }

    public function test_disabled_product_and_empty_cart_do_not_write_a_sale(): void
    {
        $product = $this->product(['is_active' => false]);
        $cashier = $this->cashier();
        foreach ([$this->input($product), $this->input($product, ['items' => []])] as $data) {
            try {
                app(SaleCheckoutService::class)->checkout($cashier, $data);
                $this->fail();
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $this->assertDatabaseCount('sales', 0);
    }
}
