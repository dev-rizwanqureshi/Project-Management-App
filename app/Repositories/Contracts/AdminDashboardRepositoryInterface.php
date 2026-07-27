<?php

namespace App\Repositories\Contracts;

use App\Models\Admin;

interface AdminDashboardRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function payload(Admin $admin): array;
}
