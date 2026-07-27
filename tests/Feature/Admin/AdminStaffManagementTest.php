<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Services\AdminRolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_admin_can_view_create_and_assign_admin_staff(): void
    {
        [$owner, $roles] = $this->ownerWithRoles();

        /** @var AdminRole $supportRole */
        $supportRole = $roles->get('support_staff');
        /** @var AdminRole $adminRole */
        $adminRole = $roles->get('admin');

        $supportAdmin = Admin::factory()->create([
            'name' => 'Riraa Support',
            'email' => 'support@riraa.test',
            'role' => 'support_staff',
            'admin_role_id' => $supportRole->id,
        ]);

        $this->actingAs($owner, 'admin')
            ->get(route('admin.admins.index', ['search' => 'support@riraa.test']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Admins/Index')
                ->where('admins.data.0.email', 'support@riraa.test')
                ->where('admins.data.0.role_name', 'Support Staff')
                ->has('roles', 3)
                ->where('can.manage_admins', true)
            );

        $this->actingAs($owner, 'admin')
            ->post(route('admin.admins.store'), [
                'name' => 'Riraa Operations',
                'email' => 'operations@riraa.test',
                'password' => 'password',
                'admin_role_id' => $adminRole->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admins', [
            'email' => 'operations@riraa.test',
            'role' => 'admin',
            'admin_role_id' => $adminRole->id,
        ]);

        $customRole = AdminRole::query()->create([
            'name' => 'Listing Auditor',
            'slug' => 'listing-auditor',
            'is_system' => false,
            'created_by' => $owner->id,
        ]);
        $customRole->permissions()->sync(
            AdminPermission::query()->where('slug', 'admin.users.view')->pluck('id')->all(),
        );

        $this->actingAs($owner, 'admin')
            ->put(route('admin.admins.update', $supportAdmin), [
                'admin_role_id' => $customRole->id,
            ])
            ->assertRedirect();

        $supportAdmin->refresh();

        $this->assertSame('listing-auditor', $supportAdmin->role);
        $this->assertSame($customRole->id, $supportAdmin->admin_role_id);
    }

    public function test_admin_staff_without_manage_permission_cannot_assign_admin_roles(): void
    {
        [$owner, $roles] = $this->ownerWithRoles();

        /** @var AdminRole $supportRole */
        $supportRole = $roles->get('support_staff');
        /** @var AdminRole $adminRole */
        $adminRole = $roles->get('admin');

        $viewerRole = AdminRole::query()->create([
            'name' => 'Admin Viewer',
            'slug' => 'admin-viewer',
            'is_system' => false,
            'created_by' => $owner->id,
        ]);
        $viewerRole->permissions()->sync(
            AdminPermission::query()->where('slug', 'admin.admins.view')->pluck('id')->all(),
        );

        $viewer = Admin::factory()->create([
            'role' => 'admin-viewer',
            'admin_role_id' => $viewerRole->id,
        ]);
        $target = Admin::factory()->create([
            'role' => 'support_staff',
            'admin_role_id' => $supportRole->id,
        ]);

        $this->actingAs($viewer, 'admin')
            ->put(route('admin.admins.update', $target), [
                'admin_role_id' => $adminRole->id,
            ])
            ->assertForbidden();
    }

    /**
     * @return array{0: Admin, 1: Collection<string, AdminRole>}
     */
    private function ownerWithRoles(): array
    {
        $owner = Admin::factory()->create([
            'role' => 'owner',
            'email' => 'owner@riraa.test',
        ]);
        $roles = app(AdminRolePermissionDefaults::class)->ensureRoles($owner);

        /** @var AdminRole|null $ownerRole */
        $ownerRole = $roles->get('owner');

        $owner->forceFill(['admin_role_id' => $ownerRole?->id])->save();

        return [$owner, $roles];
    }
}
