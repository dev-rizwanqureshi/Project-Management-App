<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\RolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('canViewAnalytics', true)
                ->has('stats', 6)
            );
    }

    public function test_admin_dashboard_hides_owner_analytics_by_default()
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->create(['role' => 'owner']);
        $admin = User::factory()->for($company)->create(['role' => 'admin']);
        $roles = app(RolePermissionDefaults::class)->ensureForCompany($company, $owner);

        $admin->forceFill(['role_id' => $roles->get('admin')?->id])->save();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('canViewAnalytics', false)
                ->has('stats', 0)
            );
    }
}
