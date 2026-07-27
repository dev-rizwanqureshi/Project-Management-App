<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\RoleManagementRepositoryInterface;
use App\Services\RolePermissionDefaults;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoleManagementRepository implements RoleManagementRepositoryInterface
{
    public function __construct(
        private readonly RolePermissionDefaults $rolePermissionDefaults,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rolesForUser(User $user): array
    {
        $this->rolePermissionDefaults->assignDefaultRoleToUser($user);

        return Role::query()
            ->with('permissions')
            ->where('company_id', $user->company_id)
            ->withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => $this->rolePayload($role))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function permissionsPage(User $user, Role $role): array
    {
        $this->ensureRoleBelongsToUserCompany($user, $role);

        $role->load('permissions')->loadCount('users');

        return [
            'role' => $this->rolePayload($role),
            'permissionGroups' => $this->permissionGroups(),
        ];
    }

    /**
     * @param  list<string>  $permissionSlugs
     */
    public function createRole(User $user, string $name, array $permissionSlugs): void
    {
        $company = $user->company;

        abort_unless($company instanceof Company, 403);

        DB::transaction(function () use ($name, $permissionSlugs, $company, $user): void {
            $role = Role::query()->create([
                'company_id' => $company->id,
                'name' => $name,
                'slug' => $this->rolePermissionDefaults->uniqueRoleSlug($company, $name),
                'is_system' => false,
                'created_by' => $user->id,
            ]);

            $permissionIds = Permission::query()
                ->whereIn('slug', $permissionSlugs)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        });
    }

    /**
     * @param  list<string>  $permissionSlugs
     */
    public function updatePermissions(User $user, Role $role, array $permissionSlugs): void
    {
        $this->ensureRoleBelongsToUserCompany($user, $role);

        abort_if($role->slug === 'owner', 403, 'Owner permissions cannot be changed.');

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
    }

    private function ensureRoleBelongsToUserCompany(User $user, Role $role): void
    {
        abort_unless($role->company_id === $user->company_id, 404);
    }

    /**
     * @return array{id: int, name: string, slug: string, is_system: bool, users_count: int, permission_slugs: list<string>, can_edit: bool}
     */
    private function rolePayload(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'is_system' => $role->is_system,
            'users_count' => (int) $role->getAttribute('users_count'),
            'permission_slugs' => array_values($role->permissions
                ->pluck('slug')
                ->map(fn (mixed $slug): string => (string) $slug)
                ->all()),
            'can_edit' => $role->slug !== 'owner',
        ];
    }

    /**
     * @return array<int, array{group: string, permissions: array<int, array{id: int, name: string, slug: string, description: string|null}>}>
     */
    private function permissionGroups(): array
    {
        return Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group')
            ->map(fn (Collection $permissions, string $group): array => [
                'group' => $group,
                'permissions' => $permissions
                    ->map(fn (Permission $permission): array => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'slug' => $permission->slug,
                        'description' => $permission->description,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
