<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use App\Models\User;

interface RoleManagementRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function rolesForUser(User $user): array;

    /**
     * @return array<string, mixed>
     */
    public function permissionsPage(User $user, Role $role): array;

    /**
     * @param  list<string>  $permissionSlugs
     */
    public function createRole(User $user, string $name, array $permissionSlugs): void;

    /**
     * @param  list<string>  $permissionSlugs
     */
    public function updatePermissions(User $user, Role $role, array $permissionSlugs): void;
}
