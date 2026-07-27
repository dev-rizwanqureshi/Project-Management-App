<?php

namespace App\Repositories\Contracts;

use App\Models\Admin;
use App\Models\AdminRole;

interface AdminRoleManagementRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function roles(): array;

    /**
     * @return array<string, mixed>
     */
    public function permissionsPage(AdminRole $role): array;

    /**
     * @param  list<string>  $permissionSlugs
     */
    public function createRole(Admin $admin, string $name, array $permissionSlugs): void;

    /**
     * @param  list<string>  $permissionSlugs
     */
    public function updatePermissions(AdminRole $role, array $permissionSlugs): void;
}
