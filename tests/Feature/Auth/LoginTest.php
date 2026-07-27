<?php

namespace Tests\Feature\Auth;

use App\Models\Admin;
use App\Models\Company;
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

    public function test_profile_endpoint_returns_null_user_when_not_authenticated(): void
    {
        $this->getJson(route('profile.show'))
            ->assertOk()
            ->assertJsonPath('user', null);
    }

    public function test_profile_endpoint_returns_correct_user_and_company_data_when_authenticated(): void
    {
        $company = Company::factory()->create([
            'name' => 'Acme Projects',
            'slug' => 'acme-projects',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Owner',
            'email' => 'owner@acme.test',
            'role' => 'owner',
        ]);

        $this->actingAs($user)
            ->getJson(route('profile.show'))
            ->assertOk()
            ->assertJsonPath('user.name', 'Test Owner')
            ->assertJsonPath('user.email', 'owner@acme.test')
            ->assertJsonPath('user.role', 'owner')
            ->assertJsonPath('user.company.name', 'Acme Projects')
            ->assertJsonPath('user.company.slug', 'acme-projects');
    }
}
