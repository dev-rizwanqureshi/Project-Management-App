<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Services\AdminRolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_settings_page(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/Index')
                ->where('account.name', 'Riraa Admin')
                ->where('account.email', 'admin@riraa.test')
                ->where('account.role', 'Admin')
            );
    }

    public function test_admin_can_update_own_profile(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.settings.profile.update'), [
                'name' => 'Riraa Operations',
                'email' => 'operations@riraa.test',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
            'name' => 'Riraa Operations',
            'email' => 'operations@riraa.test',
        ]);
    }

    public function test_admin_can_update_own_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $admin->refresh()->password));
    }

    public function test_admin_password_update_requires_current_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrors('current_password');
    }

    private function admin(): Admin
    {
        $admin = Admin::factory()->create([
            'name' => 'Riraa Admin',
            'email' => 'admin@riraa.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $roles = app(AdminRolePermissionDefaults::class)->ensureRoles($admin);

        $admin->forceFill(['admin_role_id' => $roles->get('admin')?->id])->save();

        return $admin;
    }
}
