<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\PromotionRedemptionService;
use App\Services\SaleCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PosIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function customer(int $visits = 5): int
    {
        return DB::table('customers')->insertGetId([
            'phone' => '0812345678', 'name' => 'Test customer', 'total_visits' => $visits,
        ]);
    }

    private function promotion(): int
    {
        return DB::table('promotions')->insertGetId([
            'name' => 'Every five visits', 'visit_required' => 5,
            'reward_type' => 'amount', 'reward_value' => 50, 'is_active' => true,
        ]);
    }

    private function sale(User $user, array $attributes = []): int
    {
        return DB::table('sales')->insertGetId(array_merge([
            'bill_no' => 'TEST-'.(DB::table('sales')->count() + 1),
            'user_id' => $user->id, 'status' => 'paid', 'created_at' => now(), 'updated_at' => now(),
        ], $attributes));
    }

    private function productSale(User $user, int $customerId): array
    {
        $sale = $this->sale($user, ['customer_id' => $customerId, 'visit_counted' => true]);
        $product = DB::table('products')->insertGetId([
            'sku' => 'SHAMPOO', 'name' => 'Shampoo', 'sell_price' => 100, 'stock_qty' => 8,
        ]);
        DB::table('sale_items')->insert([
            'sale_id' => $sale, 'itemable_type' => Product::class, 'itemable_id' => $product,
            'item_name' => 'Shampoo', 'qty' => 2, 'price' => 100, 'subtotal' => 200,
        ]);
        $movement = DB::table('stock_movements')->insertGetId([
            'product_id' => $product, 'user_id' => $user->id, 'sale_id' => $sale,
            'type' => 'out', 'qty' => 2, 'stock_after' => 8,
        ]);

        return [$sale, $product, $movement];
    }

    public function test_renaming_status_preserves_existing_values_and_is_reversible(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $migration = require database_path('migrations/2026_10_19_000001_standardize_user_active_status.php');
        $migration->down();
        $this->assertFalse((bool) DB::table('users')->where('id', $user->id)->value('is_activate'));
        $migration->up();
        $this->assertTrue(Schema::hasColumn('users', 'is_active'));
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_audit_schema_can_be_rolled_back_and_applied_again(): void
    {
        $migration = require database_path('migrations/2026_10_19_000002_add_sale_audit_records.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('promotion_redemptions'));
        $this->assertFalse(Schema::hasColumn('stock_movements', 'sale_id'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('payment_refunds'));
        $this->assertTrue(Schema::hasColumn('sales', 'cancelled_at'));
    }

    public function test_an_existing_session_for_a_disabled_user_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_deactivating_a_cashier_preserves_their_sales(): void
    {
        $user = User::factory()->create();
        $sale = $this->sale($user);
        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseHas('sales', ['id' => $sale, 'user_id' => $user->id]);
    }

    public function test_a_promotion_milestone_cannot_be_used_twice_but_the_next_one_can(): void
    {
        $user = User::factory()->create();
        $customer = $this->customer();
        $promotion = $this->promotion();
        $first = $this->sale($user, ['customer_id' => $customer, 'promotion_id' => $promotion]);
        $second = $this->sale($user, ['customer_id' => $customer, 'promotion_id' => $promotion]);
        $service = app(PromotionRedemptionService::class);
        $service->redeem($first);

        try {
            $service->redeem($second);
            $this->fail('Duplicate milestone was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('promotion', $exception->errors());
        }

        DB::table('customers')->where('id', $customer)->update(['total_visits' => 10]);
        $service->redeem($second);
        $this->assertDatabaseCount('promotion_redemptions', 2);
        $this->assertDatabaseHas('promotion_redemptions', ['sale_id' => $second, 'active_milestone' => 10]);
    }

    public function test_promotion_requires_enough_visits(): void
    {
        $user = User::factory()->create();
        $sale = $this->sale($user, ['customer_id' => $this->customer(4), 'promotion_id' => $this->promotion()]);
        $this->expectException(ValidationException::class);
        app(PromotionRedemptionService::class)->redeem($sale);
    }

    public function test_cancellation_reverses_stock_points_and_rewards_once_and_queues_a_refund(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = $this->customer();
        [$sale, $product, $movement] = $this->productSale($admin, $customer);
        $promotion = $this->promotion();
        DB::table('sales')->where('id', $sale)->update(['promotion_id' => $promotion]);
        app(PromotionRedemptionService::class)->redeem($sale);
        $points = DB::table('point_transactions')->insertGetId([
            'customer_id' => $customer, 'sale_id' => $sale, 'type' => 'earn', 'points' => 10, 'balance_after' => 10,
        ]);
        $payment = DB::table('payments')->insertGetId([
            'sale_id' => $sale, 'method' => 'cash', 'amount' => 200, 'received' => 500, 'change' => 300,
        ]);

        $service = app(SaleCancellationService::class);
        $service->cancel($sale, $admin, 'Customer cancelled');
        $service->cancel($sale, $admin, 'Repeated request');

        $this->assertDatabaseHas('sales', ['id' => $sale, 'status' => 'cancelled', 'cancelled_by' => $admin->id]);
        $this->assertDatabaseHas('products', ['id' => $product, 'stock_qty' => 10]);
        $this->assertDatabaseHas('stock_movements', ['reversal_of_id' => $movement, 'type' => 'in', 'qty' => 2]);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('point_transactions', ['reversal_of_id' => $points, 'points' => -10, 'balance_after' => 0]);
        $this->assertDatabaseCount('point_transactions', 2);
        $this->assertDatabaseHas('customers', ['id' => $customer, 'total_visits' => 4]);
        $this->assertDatabaseHas('promotion_redemptions', ['sale_id' => $sale, 'active_milestone' => null]);
        $this->assertDatabaseHas('payment_refunds', ['payment_id' => $payment, 'amount' => 200, 'status' => 'pending']);
        $this->assertDatabaseCount('payment_refunds', 1);
    }

    public function test_cancellation_rolls_back_every_change_if_earned_points_have_been_spent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = $this->customer();
        [$sale, $product] = $this->productSale($admin, $customer);
        DB::table('point_transactions')->insert([
            ['customer_id' => $customer, 'sale_id' => $sale, 'type' => 'earn', 'points' => 10, 'balance_after' => 10],
            ['customer_id' => $customer, 'sale_id' => null, 'type' => 'redeem', 'points' => 10, 'balance_after' => 0],
        ]);

        try {
            app(SaleCancellationService::class)->cancel($sale, $admin, 'Cancel');
            $this->fail('Cancellation with insufficient points was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('sale', $exception->errors());
        }

        $this->assertDatabaseHas('sales', ['id' => $sale, 'status' => 'paid']);
        $this->assertDatabaseHas('products', ['id' => $product, 'stock_qty' => 8]);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('point_transactions', 2);
        $this->assertDatabaseCount('payment_refunds', 0);
    }

    public function test_a_paid_product_sale_without_linked_stock_records_cannot_be_cancelled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$sale] = $this->productSale($admin, $this->customer());
        DB::table('stock_movements')->where('sale_id', $sale)->update(['sale_id' => null]);
        $this->expectException(ValidationException::class);
        app(SaleCancellationService::class)->cancel($sale, $admin, 'Cancel');
    }

    public function test_cashiers_cannot_cancel_sales(): void
    {
        $cashier = User::factory()->create();
        $sale = $this->sale($cashier);
        $this->expectException(HttpException::class);
        app(SaleCancellationService::class)->cancel($sale, $cashier, 'Cancel');
    }

    public function test_pending_service_bills_can_be_cancelled_without_creating_refunds(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sale = $this->sale($admin, ['status' => 'pending']);
        app(SaleCancellationService::class)->cancel($sale, $admin, 'Cancel unpaid bill');
        $this->assertDatabaseHas('sales', ['id' => $sale, 'status' => 'cancelled']);
        $this->assertDatabaseCount('payment_refunds', 0);
    }

    public function test_cancelling_redeemed_points_returns_them_to_the_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = $this->customer();
        $sale = $this->sale($admin, ['customer_id' => $customer]);
        DB::table('point_transactions')->insert([
            ['customer_id' => $customer, 'sale_id' => null, 'type' => 'earn', 'points' => 20, 'balance_after' => 20],
            ['customer_id' => $customer, 'sale_id' => $sale, 'type' => 'redeem', 'points' => 5, 'balance_after' => 15],
        ]);
        app(SaleCancellationService::class)->cancel($sale, $admin, 'Return used points');
        $this->assertDatabaseHas('point_transactions', ['sale_id' => $sale, 'type' => 'adjust', 'points' => 5, 'balance_after' => 20]);
    }

    public function test_cancelling_a_reward_releases_it_for_a_future_bill(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $attributes = ['customer_id' => $this->customer(), 'promotion_id' => $this->promotion()];
        $first = $this->sale($admin, $attributes);
        app(PromotionRedemptionService::class)->redeem($first);
        app(SaleCancellationService::class)->cancel($first, $admin, 'Cancel');
        $second = $this->sale($admin, $attributes);
        app(PromotionRedemptionService::class)->redeem($second);
        $this->assertDatabaseCount('promotion_redemptions', 2);
        $this->assertDatabaseHas('promotion_redemptions', ['sale_id' => $second, 'active_milestone' => 5]);
    }
}
