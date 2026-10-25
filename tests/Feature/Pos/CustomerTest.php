<?php

namespace Tests\Feature\Pos;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\PosFixtures;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use PosFixtures, RefreshDatabase;

    public function test_cashier_can_manage_customers_but_cannot_overwrite_visits(): void
    {
        $this->actingAs($this->cashier())->post('/customers', ['name' => 'Alice', 'phone' => '0811111111', 'total_visits' => 999])->assertRedirect();
        $this->assertDatabaseHas('customers', ['name' => 'Alice', 'total_visits' => 0]);
        $customer = Customer::first();
        $this->patch('/customers/'.$customer->id, ['name' => 'Bob', 'phone' => '0811111111'])->assertRedirect();
        $this->assertSame('Bob', $customer->fresh()->name);
        $this->get('/customers?q=Bob')->assertOk()->assertSee('Bob');
        $this->get('/customers?q=missing')->assertDontSee('Bob');
    }

    public function test_duplicate_phone_and_unauthorized_access_are_rejected(): void
    {
        $customer = $this->customer();
        $this->actingAs($this->admin())->post('/customers', ['phone' => $customer->phone])->assertSessionHasErrors('phone');
        $this->actingAs($this->stylist())->get('/customers')->assertForbidden();
    }

    public function test_customer_names_are_escaped_and_history_is_displayed(): void
    {
        $customer = $this->customer(['name' => '<script>alert(1)</script>']);
        DB::table('sales')->insert(['bill_no' => 'HISTORY', 'user_id' => $this->cashier()->id, 'customer_id' => $customer->id, 'status' => 'cancelled']);
        $this->actingAs($this->admin())->get('/customers/'.$customer->id)->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertSee('HISTORY');
    }
}
