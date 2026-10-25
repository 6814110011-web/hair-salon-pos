<?php

namespace Tests\Feature\Pos;

use App\Models\Product;
use App\Services\SaleCancellationService;
use App\Services\SaleCheckoutService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\PosFixtures;
use Tests\TestCase;

class ReviewRegressionTest extends TestCase
{
    use PosFixtures, RefreshDatabase;

    private function input(Product $product): array
    {
        return ['checkout_token' => (string) Str::uuid(), 'method' => 'cash', 'received' => '200.00', 'items' => [['type' => 'product', 'id' => $product->id, 'qty' => 1, 'expected_price' => '100.00']]];
    }

    public function test_cancellation_stock_overflow_rejects_and_rolls_back(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $sale = app(SaleCheckoutService::class)->checkout($admin, $this->input($product));
        app(StockService::class)->adjust($admin, $product, 2147483647, 'Counted stock');
        try {
            app(SaleCancellationService::class)->cancel($sale->id, $admin, 'Cancel');
            $this->fail('Overflow accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('sale', $e->errors());
        }
        $this->assertSame(2147483647, $product->fresh()->stock_qty);
        $this->assertSame('paid', $sale->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseCount('payment_refunds', 0);
    }

    public function test_malformed_scalar_input_redisplays_checkout_instead_of_crashing(): void
    {
        $product = $this->product();
        $input = $this->input($product);
        $input['received'] = ['100'];
        $input['ref_no'] = ['not-a-string'];
        $this->actingAs($this->cashier())->from('/sales/create')->post('/sales', $input)->assertRedirect('/sales/create')->assertSessionHasErrors(['received', 'ref_no']);
        $this->get('/sales/create')->assertOk()->assertSee('pos-data');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_sparse_cart_is_encoded_as_a_list_after_validation_error(): void
    {
        $product = $this->product();
        $input = $this->input($product);
        $input['items'] = [1 => $input['items'][0]];
        $input['received'] = '1.00';
        $this->actingAs($this->cashier())->from('/sales/create')->post('/sales', $input)->assertSessionHasErrors('received');
        $response = $this->get('/sales/create')->assertOk();
        preg_match('/<script id="pos-data" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $json = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue(array_is_list($json['oldItems']));
        $this->assertCount(1, $json['oldItems']);
        $this->assertSame($product->id, $json['oldItems'][0]['id']);
    }

    public function test_invalid_customer_name_array_is_redisplayed_safely(): void
    {
        $this->actingAs($this->cashier())->from('/customers/create')->post('/customers', ['name' => ['invalid'], 'phone' => '0811111111'])->assertSessionHasErrors('name');
        $this->get('/customers/create')->assertOk();
    }
}
