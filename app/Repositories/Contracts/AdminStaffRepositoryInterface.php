<?php

namespace App\Repositories\Contracts;

use App\Models\Admin;
use Illuminate\Http\Request;

interface AdminStaffRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function indexPayload(Admin $admin, Request $request): array;

    /**
     * @param  array{name: string, email: string, password?: string|null, admin_role_id: int}  $data
     */
    public function create(array $data): void;

    public function assignRole(Admin $admin, int $roleId): void;
}
