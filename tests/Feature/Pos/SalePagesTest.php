<?php

namespace Tests\Feature\Pos;

use App\Models\Sale;
use App\Services\SaleCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\PosFixtures;
use Tests\TestCase;

class SalePagesTest extends TestCase
{
    use PosFixtures, RefreshDatabase;

    private function input(int $id): array
    {
        return ['checkout_token' => (string) Str::uuid(), 'method' => 'cash', 'received' => '500.00', 'items' => [['type' => 'product', 'id' => $id, 'qty' => 1, 'expected_price' => '100.00']]];
    }

    public function test_checkout_form_and_receipt_use_safe_catalogue_and_history(): void
    {
        $cashier = $this->cashier();
        $product = $this->product(['name' => '</script><script>alert(1)</script>', 'cost_price' => '8765.43']);
        $this->actingAs($cashier)->get('/sales/create')->assertOk()->assertSee('name="checkout_token"', false)->assertSee('name="received"', false)->assertSee('/js/pos.js', false)->assertDontSee('</script><script>alert(1)</script>', false)->assertDontSee('8765.43');
        $response = $this->post('/sales', $this->input($product->id))->assertRedirect();
        $sale = Sale::firstOrFail();
        $product->update(['name' => 'New catalogue name', 'sell_price' => '999.00']);
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('100.00')->assertSee('400.00')->assertDontSee('New catalogue name')->assertSee('data-print', false);
        $this->actingAs($this->cashier())->get('/sales/'.$sale->id)->assertForbidden();
        $this->actingAs($this->admin())->get('/sales/'.$sale->id)->assertOk();
        $this->actingAs($this->stylist())->get('/sales')->assertForbidden();
    }

    public function test_invalid_checkout_preserves_cart_and_filters_list_by_owner(): void
    {
        $product = $this->product();
        $cashier = $this->cashier();
        $input = $this->input($product->id);
        $input['received'] = '1.00';
        $this->actingAs($cashier)->from('/sales/create')->post('/sales', $input)->assertRedirect('/sales/create')->assertSessionHasErrors('received')->assertSessionHasInput('items.0.id', $product->id);
        $sale = app(SaleCheckoutService::class)->checkout($cashier, $this->input($product->id));
        $this->get('/sales?q='.$sale->bill_no)->assertOk()->assertSee($sale->bill_no);
        $this->actingAs($this->cashier())->get('/sales')->assertOk()->assertDontSee($sale->bill_no);
    }
}
