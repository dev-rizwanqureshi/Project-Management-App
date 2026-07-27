<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\RolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_roles()
    {
        [$owner] = $this->usersWithRoles();

        $response = $this->actingAs($owner)->get(route('roles.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Index')
                ->has('roles', 4)
            );
    }

    public function test_owner_can_open_a_role_permissions_page()
    {
        [$owner, , , $roles] = $this->usersWithRoles();

        /** @var Role $adminRole */
        $adminRole = $roles->get('admin');

        $response = $this->actingAs($owner)->get(route('roles.permissions.edit', $adminRole));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Permissions')
                ->where('role.name', 'Admin')
                ->has('permissionGroups')
            );
    }

    public function test_admin_cannot_manage_roles_by_default()
    {
        [, $admin] = $this->usersWithRoles();

        $response = $this->actingAs($admin)->get(route('roles.index'));

        $response->assertForbidden();
    }

    public function test_owner_can_update_admin_permissions()
    {
        [$owner, , , $roles] = $this->usersWithRoles();

        /** @var Role $adminRole */
        $adminRole = $roles->get('admin');

        $response = $this->actingAs($owner)->put(
            route('roles.permissions.update', $adminRole),
            [
                'permissions' => [
                    'dashboard.view',
                    'roles.view',
                ],
            ],
        );

        $response->assertRedirect();

        $adminRole->refresh();

        $this->assertTrue($adminRole->permissions()->where('slug', 'roles.view')->exists());
        $this->assertFalse($adminRole->permissions()->where('slug', 'cards.manage')->exists());
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3: Collection<string, Role>}
     */
    private function usersWithRoles(): array
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->create(['role' => 'owner']);
        $admin = User::factory()->for($company)->create(['role' => 'admin']);
        $member = User::factory()->for($company)->create(['role' => 'member']);

        $roles = app(RolePermissionDefaults::class)->ensureForCompany($company, $owner);

        foreach ([$owner, $admin, $member] as $user) {
            $user->forceFill(['role_id' => $roles->get($user->role)?->id])->save();
        }

        return [$owner, $admin, $member, $roles];
    }
}
