<?php

namespace Tests\Feature\Auth;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_logged_in_admin_is_redirected_from_user_login_to_admin_dashboard(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_user_with_correct_credentials_can_log_in(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Logged in successfully');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_with_incorrect_credentials_cannot_log_in(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Invalid credentials');

        $this->assertGuest();
    }
}
