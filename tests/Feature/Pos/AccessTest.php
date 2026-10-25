<?php

namespace Tests\Feature\Pos;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_closed(): void
    {
        $this->get('/register')->assertForbidden();
        $this->post('/register', ['name' => 'Stranger', 'email' => 'x@example.com', 'password' => 'password', 'password_confirmation' => 'password'])->assertForbidden();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_roles_are_enforced_on_endpoints(): void
    {
        Route::middleware(['web', 'auth', 'role:admin'])->get('/role-probe', fn () => 'Allowed');
        $this->get('/role-probe')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/role-probe')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/role-probe')->assertOk();
    }

    public function test_the_last_admin_cannot_deactivate_their_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->from('/profile')->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'account');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_command_promotes_only_the_selected_account(): void
    {
        $chosen = User::factory()->create(['email' => 'chosen@example.com']);
        $other = User::factory()->create();
        $this->artisan('salon:admin')->expectsQuestion('อีเมลผู้ดูแลระบบ', 'chosen@example.com')
            ->expectsConfirmation('เปลี่ยนบัญชีนี้เป็นผู้ดูแลระบบหรือไม่?', 'yes')->assertSuccessful();
        $this->assertSame('admin', $chosen->fresh()->role);
        $this->assertSame('cashier', $other->fresh()->role);
    }

    public function test_checkout_schema_is_reversible_without_losing_legacy_sales(): void
    {
        $user = User::factory()->create();
        $id = DB::table('sales')->insertGetId(['bill_no' => 'OLD', 'user_id' => $user->id]);
        $migration = require database_path('migrations/2026_10_19_000003_add_checkout_and_refund_confirmation.php');
        $migration->down();
        $migration->up();
        $this->assertDatabaseHas('sales', ['id' => $id, 'checkout_token' => null]);
    }
}
