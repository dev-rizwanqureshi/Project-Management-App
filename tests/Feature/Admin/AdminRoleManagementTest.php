<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AdminRole;
use App\Services\AdminRolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_admin_can_view_admin_roles()
    {
        [$owner] = $this->adminsWithRoles();

        $response = $this->actingAs($owner, 'admin')->get(route('admin.roles.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Roles/Index')
                ->has('roles', 3)
            );
    }

    public function test_admin_cannot_manage_admin_roles_by_default()
    {
        [, $adminStaff] = $this->adminsWithRoles();

        $response = $this->actingAs($adminStaff, 'admin')->get(route('admin.roles.index'));

        $response->assertForbidden();
    }

    public function test_owner_admin_can_open_admin_role_permissions_page()
    {
        [$owner, , , $roles] = $this->adminsWithRoles();

        /** @var AdminRole $supportRole */
        $supportRole = $roles->get('support_staff');

        $response = $this->actingAs($owner, 'admin')->get(route('admin.roles.permissions.edit', $supportRole));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Roles/Permissions')
                ->where('role.name', 'Support Staff')
                ->has('permissionGroups')
            );
    }

    public function test_owner_admin_can_update_support_permissions()
    {
        [$owner, , , $roles] = $this->adminsWithRoles();

        /** @var AdminRole $supportRole */
        $supportRole = $roles->get('support_staff');

        $response = $this->actingAs($owner, 'admin')->put(
            route('admin.roles.permissions.update', $supportRole),
            [
                'permissions' => [
                    'admin.dashboard.view',
                    'admin.reports.view',
                ],
            ],
        );

        $response->assertRedirect();

        $supportRole->refresh();

        $this->assertTrue($supportRole->permissions()->where('slug', 'admin.reports.view')->exists());
        $this->assertFalse($supportRole->permissions()->where('slug', 'admin.cards.view')->exists());
    }

    public function test_default_admin_roles_separate_listing_view_from_restriction_permissions(): void
    {
        [, $adminStaff, $support, $roles] = $this->adminsWithRoles();

        /** @var AdminRole $adminRole */
        $adminRole = $roles->get($adminStaff->role);
        /** @var AdminRole $supportRole */
        $supportRole = $roles->get($support->role);

        foreach (['companies', 'users', 'workspaces', 'boards', 'cards'] as $resource) {
            $this->assertTrue($supportRole->permissions()->where('slug', "admin.{$resource}.view")->exists());
            $this->assertFalse($supportRole->permissions()->where('slug', "admin.{$resource}.restrict")->exists());

            $this->assertTrue($adminRole->permissions()->where('slug', "admin.{$resource}.view")->exists());
            $this->assertTrue($adminRole->permissions()->where('slug', "admin.{$resource}.restrict")->exists());
        }
    }

    /**
     * @return array{0: Admin, 1: Admin, 2: Admin, 3: Collection<string, AdminRole>}
     */
    private function adminsWithRoles(): array
    {
        $owner = Admin::factory()->create(['role' => 'owner']);
        $adminStaff = Admin::factory()->create(['role' => 'admin']);
        $support = Admin::factory()->create(['role' => 'support_staff']);

        $roles = app(AdminRolePermissionDefaults::class)->ensureRoles($owner);

        foreach ([$owner, $adminStaff, $support] as $admin) {
            $admin->forceFill(['admin_role_id' => $roles->get($admin->role)?->id])->save();
        }

        return [$owner, $adminStaff, $support, $roles];
    }
}
