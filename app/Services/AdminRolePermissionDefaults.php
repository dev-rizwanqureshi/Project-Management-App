<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdminRolePermissionDefaults
{
    /**
     * @return array<int, array{group: string, name: string, slug: string, description: string}>
     */
    public function permissions(): array
    {
        return [
            ['group' => 'Admin Dashboard', 'name' => 'View admin dashboard', 'slug' => 'admin.dashboard.view', 'description' => 'Open the platform admin dashboard.'],
            ['group' => 'Admin Management', 'name' => 'View admin users', 'slug' => 'admin.admins.view', 'description' => 'See platform admin accounts.'],
            ['group' => 'Admin Management', 'name' => 'Manage admin users', 'slug' => 'admin.admins.manage', 'description' => 'Create and update platform admin accounts.'],
            ['group' => 'Admin Management', 'name' => 'View admin roles', 'slug' => 'admin.roles.view', 'description' => 'See admin roles and permission assignments.'],
            ['group' => 'Admin Management', 'name' => 'Manage admin roles', 'slug' => 'admin.roles.manage', 'description' => 'Create admin role types and change page access.'],
            ['group' => 'Companies Listing', 'name' => 'View companies listing', 'slug' => 'admin.companies.view', 'description' => 'See companies registered in Riraa.'],
            ['group' => 'Companies Listing', 'name' => 'Restrict companies', 'slug' => 'admin.companies.restrict', 'description' => 'Use restriction controls for companies.'],
            ['group' => 'Users Listing', 'name' => 'View users listing', 'slug' => 'admin.users.view', 'description' => 'See users who belong to customer companies.'],
            ['group' => 'Users Listing', 'name' => 'Restrict users', 'slug' => 'admin.users.restrict', 'description' => 'Use restriction controls for company users.'],
            ['group' => 'Workspaces Listing', 'name' => 'View workspaces listing', 'slug' => 'admin.workspaces.view', 'description' => 'See workspaces created by company users.'],
            ['group' => 'Workspaces Listing', 'name' => 'Restrict workspaces', 'slug' => 'admin.workspaces.restrict', 'description' => 'Use restriction controls for workspaces.'],
            ['group' => 'Boards Listing', 'name' => 'View boards listing', 'slug' => 'admin.boards.view', 'description' => 'See boards created by company users.'],
            ['group' => 'Boards Listing', 'name' => 'Restrict boards', 'slug' => 'admin.boards.restrict', 'description' => 'Use restriction controls for boards.'],
            ['group' => 'Tickets Listing', 'name' => 'View tickets listing', 'slug' => 'admin.cards.view', 'description' => 'See tickets/cards across the platform.'],
            ['group' => 'Tickets Listing', 'name' => 'Restrict tickets', 'slug' => 'admin.cards.restrict', 'description' => 'Use restriction controls for tickets/cards.'],
            ['group' => 'Reporting', 'name' => 'View platform reports', 'slug' => 'admin.reports.view', 'description' => 'See platform-level reports and audit summaries.'],
            ['group' => 'Settings', 'name' => 'Manage platform settings', 'slug' => 'admin.settings.manage', 'description' => 'Change global Riraa configuration.'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public function defaultRolePermissions(): array
    {
        return [
            'owner' => $this->permissionSlugs(),
            'admin' => [
                'admin.dashboard.view',
                'admin.admins.view',
                'admin.admins.manage',
                'admin.roles.view',
                'admin.companies.view',
                'admin.companies.restrict',
                'admin.users.view',
                'admin.users.restrict',
                'admin.workspaces.view',
                'admin.workspaces.restrict',
                'admin.boards.view',
                'admin.boards.restrict',
                'admin.cards.view',
                'admin.cards.restrict',
                'admin.reports.view',
                'admin.settings.manage',
            ],
            'support_staff' => [
                'admin.dashboard.view',
                'admin.companies.view',
                'admin.users.view',
                'admin.workspaces.view',
                'admin.boards.view',
                'admin.cards.view',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function permissionSlugs(): array
    {
        return array_column($this->permissions(), 'slug');
    }

    /**
     * @return Collection<string, AdminPermission>
     */
    public function ensurePermissions(): Collection
    {
        foreach ($this->permissions() as $permission) {
            AdminPermission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                $permission,
            );
        }

        return AdminPermission::query()
            ->whereIn('slug', $this->permissionSlugs())
            ->get()
            ->keyBy('slug');
    }

    /**
     * @return Collection<string, AdminRole>
     */
    public function ensureRoles(?Admin $creator = null): Collection
    {
        $this->migrateLegacyRoles();

        $permissions = $this->ensurePermissions();
        $roles = collect();

        foreach (['owner' => 'Owner', 'admin' => 'Admin', 'support_staff' => 'Support Staff'] as $slug => $name) {
            $role = AdminRole::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'is_system' => true,
                    'created_by' => $creator?->id,
                ],
            );

            $defaultPermissionIds = $permissions
                ->filter(fn (AdminPermission $permission): bool => in_array(
                    $permission->slug,
                    $this->defaultRolePermissions()[$slug],
                    true,
                ))
                ->pluck('id')
                ->all();

            if ($role->wasRecentlyCreated || $slug === 'owner' || ! $role->permissions()->exists()) {
                $role->permissions()->sync($defaultPermissionIds);
            } elseif ($slug === 'admin') {
                $role->permissions()->syncWithoutDetaching($defaultPermissionIds);
            }

            $roles->put($slug, $role);
        }

        return $roles;
    }

    public function assignDefaultRoleToAdmin(Admin $admin): void
    {
        $roles = $this->ensureRoles($admin);
        $slug = $this->normalizeRoleSlug($admin->role);

        if ($slug !== $admin->role) {
            $admin->forceFill(['role' => $slug])->save();
        }

        $role = $roles->get($slug);

        if (! $role) {
            return;
        }

        $admin->forceFill(['admin_role_id' => $role->id])->save();
        $admin->setRelation('adminRole', $role->load('permissions'));
    }

    public function uniqueRoleSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'admin-role';
        $slug = $baseSlug;
        $counter = 2;

        while (AdminRole::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function normalizeRoleSlug(string $slug): string
    {
        return match ($slug) {
            'super_admin' => 'admin',
            'support' => 'support_staff',
            default => $slug,
        };
    }

    private function migrateLegacyRoles(): void
    {
        $this->migrateLegacyRole('super_admin', 'admin', 'Admin');
        $this->migrateLegacyRole('support', 'support_staff', 'Support Staff');
    }

    private function migrateLegacyRole(string $fromSlug, string $toSlug, string $name): void
    {
        Admin::query()
            ->where('role', $fromSlug)
            ->update(['role' => $toSlug]);

        $from = AdminRole::query()->where('slug', $fromSlug)->first();

        if (! $from) {
            return;
        }

        $to = AdminRole::query()->where('slug', $toSlug)->first();

        if (! $to) {
            $from->forceFill([
                'name' => $name,
                'slug' => $toSlug,
            ])->save();

            return;
        }

        Admin::query()
            ->where('admin_role_id', $from->id)
            ->update(['admin_role_id' => $to->id]);

        $to->permissions()->syncWithoutDetaching(
            $from->permissions()->pluck('admin_permissions.id')->all(),
        );

        $from->delete();
    }
}
