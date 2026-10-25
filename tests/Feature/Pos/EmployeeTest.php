<?php

namespace Tests\Feature\Pos;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\PosFixtures;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use PosFixtures, RefreshDatabase;

    private function attributes(array $overrides = []): array
    {
        return array_merge(['name' => 'New stylist', 'email' => 'stylist@example.com', 'role' => 'stylist', 'commission_rate' => '10', 'is_active' => '1', 'password' => 'password123', 'password_confirmation' => 'password123'], $overrides);
    }

    public function test_admin_can_create_and_update_staff_without_replacing_blank_password(): void
    {
        $this->actingAs($this->admin())->post('/employees', $this->attributes())->assertRedirect();
        $user = User::where('email', 'stylist@example.com')->firstOrFail();
        $hash = $user->password;
        $this->assertTrue(Hash::check('password123', $hash));
        $this->patch('/employees/'.$user->id, $this->attributes(['password' => '', 'password_confirmation' => '', 'name' => 'Updated']))->assertRedirect();
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame('Updated', $user->fresh()->name);
        $this->get('/employees?q=Updated')->assertOk()->assertSee('Updated')->assertDontSee($hash);
    }

    public function test_cashier_cannot_manage_staff_and_invalid_commission_is_rejected(): void
    {
        $this->actingAs($this->cashier())->get('/employees')->assertForbidden();
        $this->post('/employees', $this->attributes())->assertForbidden();
        $this->actingAs($this->admin())->post('/employees', $this->attributes(['commission_rate' => '100.01']))->assertSessionHasErrors('commission_rate');
    }

    public function test_last_admin_cannot_be_disabled_or_demoted(): void
    {
        $admin = $this->admin();
        $disabled = User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $this->actingAs($admin)->patch('/employees/'.$admin->id, $this->attributes(['email' => $admin->email, 'role' => 'cashier']))->assertSessionHasErrors('account');
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertFalse($disabled->fresh()->is_active);
    }

    public function test_profile_cannot_promote_a_cashier(): void
    {
        $user = $this->cashier();
        $this->actingAs($user)->patch('/profile', ['name' => $user->name, 'email' => $user->email, 'role' => 'admin'])->assertRedirect();
        $this->assertSame('cashier', $user->fresh()->role);
    }
}
