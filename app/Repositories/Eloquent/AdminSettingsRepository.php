<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Repositories\Contracts\AdminSettingsRepositoryInterface;

class AdminSettingsRepository implements AdminSettingsRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function payload(Admin $admin): array
    {
        $roleName = $admin->adminRole()->value('name');

        return [
            'account' => [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => is_string($roleName) && $roleName !== ''
                    ? $roleName
                    : str($admin->role)->replace('_', ' ')->headline()->toString(),
            ],
        ];
    }

    /**
     * @param  array{name: string, email: string}  $data
     */
    public function updateProfile(Admin $admin, array $data): void
    {
        $admin->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
        ])->save();
    }

    public function updatePassword(Admin $admin, string $password): void
    {
        $admin->forceFill(['password' => $password])->save();
    }
}
