<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RolePermissionDefaults
{
    /**
     * @return array<int, array{group: string, name: string, slug: string, description: string}>
     */
    public function permissions(): array
    {
        return [
            ['group' => 'Dashboard', 'name' => 'View dashboard', 'slug' => 'dashboard.view', 'description' => 'Open the dashboard page.'],
            ['group' => 'Dashboard', 'name' => 'View owner analytics', 'slug' => 'dashboard.analytics', 'description' => 'See company-wide counts and charts.'],
            ['group' => 'Company', 'name' => 'View company', 'slug' => 'company.view', 'description' => 'See company profile and subscription status.'],
            ['group' => 'Company', 'name' => 'View users', 'slug' => 'users.view', 'description' => 'See users in the company.'],
            ['group' => 'Company', 'name' => 'Manage users', 'slug' => 'users.manage', 'description' => 'Invite, edit, and deactivate company users.'],
            ['group' => 'Workspaces', 'name' => 'View workspaces', 'slug' => 'workspaces.view', 'description' => 'See workspaces and workspace details.'],
            ['group' => 'Workspaces', 'name' => 'Manage workspaces', 'slug' => 'workspaces.manage', 'description' => 'Create, edit, archive, and delete workspaces.'],
            ['group' => 'Boards & Cards', 'name' => 'View boards', 'slug' => 'boards.view', 'description' => 'See boards and board members.'],
            ['group' => 'Boards & Cards', 'name' => 'Manage boards', 'slug' => 'boards.manage', 'description' => 'Create, edit, archive, and delete boards.'],
            ['group' => 'Boards & Cards', 'name' => 'View cards', 'slug' => 'cards.view', 'description' => 'See task cards and ticket details.'],
            ['group' => 'Boards & Cards', 'name' => 'Manage cards', 'slug' => 'cards.manage', 'description' => 'Create, edit, assign, archive, and complete cards.'],
            ['group' => 'Administration', 'name' => 'View roles', 'slug' => 'roles.view', 'description' => 'See roles and their permission matrix.'],
            ['group' => 'Administration', 'name' => 'Manage roles', 'slug' => 'roles.manage', 'description' => 'Create roles and change role permissions.'],
            ['group' => 'Administration', 'name' => 'View reports', 'slug' => 'reports.view', 'description' => 'See reporting and audit views.'],
            ['group' => 'Administration', 'name' => 'Manage settings', 'slug' => 'settings.manage', 'description' => 'Change company and application settings.'],
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
                'dashboard.view',
                'company.view',
                'users.view',
                'users.manage',
                'workspaces.view',
                'workspaces.manage',
                'boards.view',
                'boards.manage',
                'cards.view',
                'cards.manage',
                'reports.view',
            ],
            'member' => [
                'dashboard.view',
                'workspaces.view',
                'boards.view',
                'cards.view',
                'cards.manage',
            ],
            'guest' => [
                'dashboard.view',
                'company.view',
                'workspaces.view',
                'boards.view',
                'cards.view',
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
     * @return Collection<string, Permission>
     */
    public function ensurePermissions(): Collection
    {
        foreach ($this->permissions() as $permission) {
            Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                $permission,
            );
        }

        return Permission::query()
            ->whereIn('slug', $this->permissionSlugs())
            ->get()
            ->keyBy('slug');
    }

    /**
     * @return Collection<string, Role>
     */
    public function ensureForCompany(Company $company, ?User $creator = null): Collection
    {
        $permissions = $this->ensurePermissions();
        $roles = collect();

        foreach (['owner' => 'Owner', 'admin' => 'Admin', 'member' => 'Member', 'guest' => 'Guest'] as $slug => $name) {
            $role = Role::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => $slug,
                ],
                [
                    'name' => $name,
                    'is_system' => true,
                    'created_by' => $creator?->id,
                ],
            );

            $shouldSyncDefaults = $role->wasRecentlyCreated
                || $slug === 'owner'
                || ! $role->permissions()->exists();

            if ($shouldSyncDefaults) {
                $role->permissions()->sync(
                    $permissions
                        ->filter(fn (Permission $permission): bool => in_array(
                            $permission->slug,
                            $this->defaultRolePermissions()[$slug],
                            true,
                        ))
                        ->pluck('id')
                        ->all(),
                );
            } elseif ($slug === 'admin' && ! $role->permissions()->where('slug', 'users.manage')->exists()) {
                $role->permissions()->syncWithoutDetaching(
                    $permissions->where('slug', 'users.manage')->pluck('id')->all(),
                );
            }

            $roles->put($slug, $role);
        }

        return $roles;
    }

    public function assignDefaultRoleToUser(User $user): void
    {
        $company = $user->company;

        if (! $company) {
            return;
        }

        $roles = $this->ensureForCompany($company, $user);
        $role = $roles->get($user->role);

        if (! $role) {
            return;
        }

        $user->forceFill(['role_id' => $role->id])->save();
        CompanyUser::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'user_id' => $user->id,
            ],
            [
                'role' => $user->role,
                'role_id' => $role->id,
                'status' => 'active',
                'joined_at' => now(),
                'left_at' => null,
            ],
        );
        $user->setRelation('roleDefinition', $role->load('permissions'));
    }

    public function uniqueRoleSlug(Company $company, string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'role';
        $slug = $baseSlug;
        $counter = 2;

        while (Role::query()
            ->where('company_id', $company->id)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
