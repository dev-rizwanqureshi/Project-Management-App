<?php

namespace App\Repositories\Contracts;

use App\Models\Admin;

interface AdminSettingsRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function payload(Admin $admin): array;

    /**
     * @param  array{name: string, email: string}  $data
     */
    public function updateProfile(Admin $admin, array $data): void;

    public function updatePassword(Admin $admin, string $password): void;
}
