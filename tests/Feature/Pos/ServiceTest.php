<?php

namespace Tests\Feature\Pos;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PosFixtures;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use PosFixtures, RefreshDatabase;

    public function test_admin_manages_services_and_invalid_values_are_rejected(): void
    {
        $data = ['name' => 'Wash', 'category' => 'wash', 'price' => '99.50', 'duration_minutes' => 30, 'is_active' => 1];
        $this->actingAs($this->admin())->post('/services', $data)->assertRedirect();
        $service = Service::firstOrFail();
        $this->patch('/services/'.$service->id, array_merge($data, ['is_active' => 0]))->assertRedirect();
        $this->assertFalse($service->fresh()->is_active);
        $this->post('/services', array_merge($data, ['category' => 'invalid', 'price' => '-1', 'duration_minutes' => 0]))->assertSessionHasErrors(['category', 'price', 'duration_minutes']);
        $this->get('/services?q=Wash&category=wash')->assertOk()->assertSee('Wash');
    }

    public function test_stylist_and_cashier_can_read_but_not_write(): void
    {
        foreach ([$this->stylist(), $this->cashier()] as $user) {
            $this->actingAs($user)->get('/services')->assertOk();
            $this->post('/services', [])->assertForbidden();
        }
    }
}
