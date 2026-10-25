<?php

namespace Tests\Feature\Pos;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Services\SalesReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\PosFixtures;
use Tests\TestCase;

class ReportDashboardTest extends TestCase
{
    use PosFixtures, RefreshDatabase;

    public function test_aggregations_do_not_multiply_items_and_payments_and_dates_are_local(): void
    {
        $seller = $this->cashier();
        $sale = Sale::forceCreate(['bill_no' => 'ONE', 'user_id' => $seller->id, 'status' => 'paid', 'subtotal' => '300.00', 'discount' => '50.00', 'total' => '250.00', 'created_at' => '2026-10-19 23:59:59']);
        foreach ([100, 200] as $amount) {
            SaleItem::forceCreate(['sale_id' => $sale->id, 'itemable_type' => Service::class, 'itemable_id' => 1, 'item_name' => 'Service', 'qty' => 1, 'price' => $amount, 'subtotal' => $amount]);
        }
        foreach ([100, 150] as $amount) {
            Payment::forceCreate(['sale_id' => $sale->id, 'method' => 'cash', 'amount' => $amount]);
        }
        Sale::forceCreate(['bill_no' => 'NEXT', 'user_id' => $seller->id, 'status' => 'paid', 'total' => '999.00', 'created_at' => '2026-10-20 00:00:00']);
        $cancelled = Sale::forceCreate(['bill_no' => 'CANCELLED', 'user_id' => $seller->id, 'status' => 'cancelled', 'total' => '500.00', 'created_at' => '2026-10-18 10:00:00', 'cancelled_at' => '2026-10-19 12:00:00']);
        $payment = Payment::forceCreate(['sale_id' => $cancelled->id, 'method' => 'cash', 'amount' => 500]);
        DB::table('payment_refunds')->insert(['payment_id' => $payment->id, 'requested_by' => $seller->id, 'amount' => 500, 'status' => 'pending']);
        $report = app(SalesReportService::class)->summary('2026-10-19', '2026-10-19');
        $this->assertSame('250.00', $report['revenue']);
        $this->assertSame(1, $report['bill_count']);
        $this->assertSame('50.00', $report['discount']);
        $this->assertSame('300.00', $report['item_totals']['service']);
        $this->assertSame('250.00', $report['payment_totals']['cash']);
        $this->assertSame('500.00', $report['pending_refunds']);
        $this->assertSame(1, $report['cancelled_count']);
    }

    public function test_empty_report_zero_average_and_role_scoping(): void
    {
        $report = app(SalesReportService::class)->summary('2026-10-19', '2026-10-19');
        $this->assertSame('0.00', $report['average_bill']);
        $this->actingAs($this->cashier())->get('/reports')->assertForbidden();
        $this->actingAs($this->admin())->get('/reports?from=2026-10-20&to=2026-10-19')->assertSessionHasErrors('to');
        $this->get('/reports?from=2026-10-19&to=2026-10-19')->assertOk();
    }

    public function test_cashier_dashboard_hides_other_sellers_and_stylist_sees_only_own_services(): void
    {
        $cashier = $this->cashier();
        $other = $this->cashier();
        $stylist = $this->stylist();
        $sale = Sale::forceCreate(['bill_no' => 'PRIVATE-BILL', 'user_id' => $other->id, 'status' => 'paid', 'total' => '8765.43', 'created_at' => now()]);
        SaleItem::forceCreate(['sale_id' => $sale->id, 'itemable_type' => Service::class, 'itemable_id' => 1, 'stylist_id' => $stylist->id, 'item_name' => 'Assigned haircut', 'qty' => 1, 'price' => 100, 'subtotal' => 100]);
        $this->actingAs($cashier)->get('/dashboard')->assertOk()->assertDontSee('PRIVATE-BILL')->assertDontSee('8765.43');
        $this->actingAs($stylist)->get('/dashboard')->assertOk()->assertSee('Assigned haircut')->assertDontSee('8765.43');
    }
}
