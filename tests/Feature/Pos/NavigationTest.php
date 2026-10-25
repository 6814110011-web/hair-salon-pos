<?php

namespace Tests\Feature\Pos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PosFixtures;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use PosFixtures, RefreshDatabase;

    public function test_admin_sees_all_modules_and_authenticated_home_redirects(): void
    {
        $this->actingAs($this->admin())->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('id="sidebar"', false)->assertSee('/customers', false)->assertSee('/employees', false)->assertSee('/products', false)->assertSee('/services', false)->assertSee('/sales', false)->assertSee('/reports', false);
    }

    public function test_navigation_respects_roles_and_public_signup_is_not_advertised(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('href="http://localhost/register"', false);
        $this->actingAs($this->cashier())->get('/dashboard')->assertOk()->assertSee('/customers', false)->assertDontSee('href="http://localhost/employees"', false)->assertDontSee('href="http://localhost/reports"', false);
        $this->actingAs($this->stylist())->get('/dashboard')->assertOk()->assertSee('/services', false)->assertDontSee('href="http://localhost/sales"', false)->assertDontSee('href="http://localhost/customers"', false);
    }
}
