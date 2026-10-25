<?php

namespace Tests\Feature\Pos;

use App\Services\SaleCancellationService;
use App\Services\SaleCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\PosFixtures;
use Tests\TestCase;

class CancellationRefundTest extends TestCase
{
    use PosFixtures, RefreshDatabase;

    public function test_admin_cancels_and_confirms_refund_only_once(): void
    {
        $cashier = $this->cashier();
        $admin = $this->admin();
        $product = $this->product();
        $sale = app(SaleCheckoutService::class)->checkout($cashier, ['checkout_token' => (string) Str::uuid(), 'method' => 'cash', 'received' => '500.00', 'items' => [['id' => $product->id, 'type' => 'product', 'qty' => 2, 'expected_price' => '100.00']]]);
        $this->actingAs($cashier)->post('/sales/'.$sale->id.'/cancel', ['reason' => 'No'])->assertForbidden();
        $this->actingAs($admin)->post('/sales/'.$sale->id.'/cancel', ['reason' => ''])->assertSessionHasErrors('reason');
        $this->post('/sales/'.$sale->id.'/cancel', ['reason' => 'Customer cancelled'])->assertRedirect();
        $refund = DB::table('payment_refunds')->first();
        $this->assertEquals(200, $refund->amount);
        $this->get('/sales/'.$sale->id)->assertOk()->assertSee('รอคืนเงิน');
        $this->post('/refunds/'.$refund->id.'/complete', ['ref_no' => 'CASH-RETURN'])->assertRedirect();
        $completed = DB::table('payment_refunds')->first();
        $this->travel(10)->minutes();
        $this->post('/refunds/'.$refund->id.'/complete', ['ref_no' => 'CHANGED'])->assertRedirect();
        $again = DB::table('payment_refunds')->first();
        $this->assertSame('CASH-RETURN', $again->ref_no);
        $this->assertSame($completed->completed_at, $again->completed_at);
        $this->assertSame($admin->id, $again->completed_by);
        $this->assertDatabaseHas('payments', ['sale_id' => $sale->id, 'amount' => 200, 'received' => 500, 'change' => 300]);
    }

    public function test_cashier_cannot_confirm_refunds(): void
    {
        $this->actingAs($this->cashier())->post('/refunds/999/complete', ['ref_no' => 'X'])->assertForbidden();
    }

    public function test_long_cancellation_reason_is_rejected_before_changes(): void
    {
        $admin = $this->admin();
        $saleId = DB::table('sales')->insertGetId(['bill_no' => 'TEST', 'user_id' => $admin->id]);
        $this->expectException(ValidationException::class);
        app(SaleCancellationService::class)->cancel($saleId, $admin, str_repeat('x', 201));
    }
}
