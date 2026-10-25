<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PlainPhpViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_escapes_user_names_and_loads_static_assets(): void
    {
        $user = User::factory()->create(['name' => '<script>alert(1)</script>']);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('/css/app.css', false)
            ->assertSee('/js/app.js', false);
    }

    public function test_login_displays_validation_errors_and_escapes_old_input(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag([
            'email' => ['Please enter a valid email.'],
        ]));

        $this->withSession([
            'errors' => $errors,
            '_old_input' => ['email' => '"><script>alert(1)</script>'],
        ])->get('/login')
            ->assertOk()
            ->assertSee('Please enter a valid email.')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_profile_displays_each_error_bag_in_its_own_section(): void
    {
        $errors = (new ViewErrorBag)
            ->put('updatePassword', new MessageBag(['password' => ['Password update failed.']]))
            ->put('userDeletion', new MessageBag(['password' => ['Account deletion failed.']]));

        $response = $this->actingAs(User::factory()->create())
            ->withSession(['errors' => $errors, 'status' => 'profile-updated'])
            ->get('/profile')
            ->assertOk()
            ->assertSee('Password update failed.')
            ->assertSee('Account deletion failed.')
            ->assertSee('บันทึกโปรไฟล์แล้ว')
            ->assertDontSee('profile-updated');

        $this->assertSame(1, substr_count($response->getContent(), 'Password update failed.'));
        $this->assertSame(1, substr_count($response->getContent(), 'Account deletion failed.'));
    }
}
