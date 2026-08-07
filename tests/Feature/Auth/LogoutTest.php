<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('logout'))
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully');

        $this->assertGuest();
    }

    public function test_logout_route_is_protected(): void
    {
        $this->postJson(route('logout'))->assertUnauthorized();
    }

    public function test_inertia_logout_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'text/html, application/xhtml+xml',
            ])
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
