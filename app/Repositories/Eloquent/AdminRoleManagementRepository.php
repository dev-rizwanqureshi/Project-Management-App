<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Repositories\Contracts\AdminRoleManagementRepositoryInterface;
use App\Services\AdminRolePermissionDefaults;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminRoleManagementRepository implements AdminRoleManagementRepositoryInterface
{
    public function __construct(
        private readonly AdminRolePermissionDefaults $adminRolePermissionDefaults,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function roles(): array
    {
        $this->adminRolePermissionDefaults->ensureRoles();

        return AdminRole::query()
            ->with('permissions')
            ->withCount('admins')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (AdminRole $role): array => $this->rolePayload($role))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function permissionsPage(AdminRole $role): array
    {
        $role->load('permissions')->loadCount('admins');

        return [
            'role' => $this->rolePayload($role),
            'permissionGroups' => $this->permissionGroups(),
        ];
    }

    /**
     * @param  list<string>  $permissionSlugs
     */
    public function createRole(Admin $admin, string $name, array $permissionSlugs): void
    {
        DB::transaction(function () use ($name, $permissionSlugs, $admin): void {
            $role = AdminRole::query()->create([
                'name' => $name,
                'slug' => $this->adminRolePermissionDefaults->uniqueRoleSlug($name),
                'is_system' => false,
                'created_by' => $admin->id,
            ]);

            $permissionIds = AdminPermission::query()
                ->whereIn('slug', $permissionSlugs)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        });
    }

    /**
     * @param  list<string>  $permissionSlugs
     */
    public function updatePermissions(AdminRole $role, array $permissionSlugs): void
    {
        abort_if($role->slug === 'owner', 403, 'Owner permissions cannot be changed.');

        $permissionIds = AdminPermission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
    }

    /**
     * @return array{id: int, name: string, slug: string, is_system: bool, admins_count: int, permission_slugs: list<string>, can_edit: bool}
     */
    private function rolePayload(AdminRole $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'is_system' => $role->is_system,
            'admins_count' => (int) $role->getAttribute('admins_count'),
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
        /** @var array<string, int> $orderBySlug */
        $orderBySlug = array_flip($this->adminRolePermissionDefaults->permissionSlugs());

        return AdminPermission::query()
            ->get()
            ->sortBy(fn (AdminPermission $permission): int => $orderBySlug[$permission->slug] ?? PHP_INT_MAX)
            ->groupBy('group')
            ->map(fn (Collection $permissions, string $group): array => [
                'group' => $group,
                'permissions' => $permissions
                    ->map(fn (AdminPermission $permission): array => [
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
