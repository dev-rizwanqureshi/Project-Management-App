<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Board;
use App\Models\Card;
use App\Models\Company;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\AdminDashboardRepositoryInterface;
use App\Services\AdminRolePermissionDefaults;

class AdminDashboardRepository implements AdminDashboardRepositoryInterface
{
    public function __construct(
        private readonly AdminRolePermissionDefaults $adminRolePermissionDefaults,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(Admin $admin): array
    {
        $this->adminRolePermissionDefaults->assignDefaultRoleToAdmin($admin);

        return [
            'stats' => [
                ['label' => 'Admin users', 'value' => Admin::query()->count(), 'helper' => 'Platform admin accounts'],
                ['label' => 'Admin roles', 'value' => AdminRole::query()->count(), 'helper' => 'Owner, admin, support staff, and custom roles'],
                ['label' => 'Companies', 'value' => Company::query()->where('is_restricted', false)->count(), 'helper' => 'Customer companies'],
                ['label' => 'Company users', 'value' => User::query()->where('is_restricted', false)->count(), 'helper' => 'Owners, admins, and members'],
                ['label' => 'Workspaces', 'value' => Workspace::query()->where('is_restricted', false)->count(), 'helper' => 'Customer workspaces'],
                ['label' => 'Boards', 'value' => Board::query()->where('is_restricted', false)->count(), 'helper' => 'Project boards'],
                ['label' => 'Tickets / cards', 'value' => Card::query()->where('is_restricted', false)->count(), 'helper' => 'All customer tickets'],
            ],
            'roleChart' => Admin::query()
                ->selectRaw('role, count(*) as admins_count')
                ->groupBy('role')
                ->orderBy('role')
                ->get()
                ->map(fn (Admin $roleCount): array => [
                    'label' => str((string) $roleCount->role)->replace('_', ' ')->title()->toString(),
                    'value' => (int) $roleCount->getAttribute('admins_count'),
                ])
                ->values()
                ->all(),
        ];
    }
}
