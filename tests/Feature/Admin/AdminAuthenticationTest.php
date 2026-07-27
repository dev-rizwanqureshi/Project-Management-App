<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use App\Services\AdminRolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_view_the_admin_login_page()
    {
        $response = $this->get(route('admin.login'));

        $response->assertOk();
    }

    public function test_admins_can_login_to_admin_dashboard()
    {
        $admin = Admin::factory()->create([
            'email' => 'owner@riraa.test',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $roles = app(AdminRolePermissionDefaults::class)->ensureRoles($admin);
        $admin->forceFill(['admin_role_id' => $roles->get('owner')?->id])->save();

        $response = $this->post(route('admin.login.store'), [
            'email' => 'owner@riraa.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_admin_login_ignores_stale_user_intended_url()
    {
        $admin = Admin::factory()->create([
            'email' => 'owner@riraa.test',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $roles = app(AdminRolePermissionDefaults::class)->ensureRoles($admin);
        $admin->forceFill(['admin_role_id' => $roles->get('owner')?->id])->save();

        $response = $this
            ->withSession(['url.intended' => route('login')])
            ->post(route('admin.login.store'), [
                'email' => 'owner@riraa.test',
                'password' => 'password',
            ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_company_users_cannot_open_admin_dashboard()
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_logged_in_company_user_is_redirected_from_admin_dashboard_to_user_dashboard()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_logged_in_company_user_is_redirected_from_admin_login_to_user_dashboard()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.login'))
            ->assertRedirect(route('dashboard'));
    }
}
