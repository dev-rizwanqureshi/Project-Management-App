<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\Card;
use App\Models\Company;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Services\RolePermissionDefaults;
use Illuminate\Database\Eloquent\Builder;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function __construct(
        private readonly RolePermissionDefaults $rolePermissionDefaults,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        if ($user->company) {
            $this->rolePermissionDefaults->assignDefaultRoleToUser($user);
        }

        $canViewAnalytics = $user->hasPermission('dashboard.analytics');

        return [
            'stats' => $canViewAnalytics ? $this->stats($user) : [],
            'ticketChart' => $canViewAnalytics ? $this->ticketChart($user) : [],
            'roleChart' => $canViewAnalytics ? $this->roleChart($user) : [],
            'canViewAnalytics' => $canViewAnalytics,
            'canManageRoles' => $user->hasPermission('roles.manage'),
        ];
    }

    /**
     * @return list<array{label: string, value: int, helper: string}>
     */
    private function stats(User $user): array
    {
        $companyId = $user->company_id;

        return [
            [
                'label' => 'Admin staff',
                'value' => User::query()->where('company_id', $companyId)->where('role', 'admin')->where('is_restricted', false)->count(),
                'helper' => 'Company admins',
            ],
            [
                'label' => 'Companies',
                'value' => Company::query()->whereKey($companyId)->where('is_restricted', false)->count(),
                'helper' => 'Current tenant',
            ],
            [
                'label' => 'Workspaces',
                'value' => Workspace::query()->where('company_id', $companyId)->where('is_restricted', false)->count(),
                'helper' => 'Active workspace count',
            ],
            [
                'label' => 'Users',
                'value' => User::query()->where('company_id', $companyId)->where('is_restricted', false)->count(),
                'helper' => 'Owner, admin, and members',
            ],
            [
                'label' => 'Tickets / cards',
                'value' => $this->companyCardsQuery($user)->count(),
                'helper' => 'All board cards',
            ],
            [
                'label' => 'System admins',
                'value' => Admin::query()->count(),
                'helper' => 'Platform support accounts',
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function ticketChart(User $user): array
    {
        return [
            [
                'label' => 'Open',
                'value' => (clone $this->companyCardsQuery($user))
                    ->where('is_completed', false)
                    ->where('is_archived', false)
                    ->count(),
            ],
            [
                'label' => 'Completed',
                'value' => (clone $this->companyCardsQuery($user))
                    ->where('is_completed', true)
                    ->count(),
            ],
            [
                'label' => 'Archived',
                'value' => (clone $this->companyCardsQuery($user))
                    ->where('is_archived', true)
                    ->count(),
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function roleChart(User $user): array
    {
        $chart = User::query()
            ->selectRaw('role, count(*) as users_count')
            ->where('company_id', $user->company_id)
            ->where('is_restricted', false)
            ->groupBy('role')
            ->orderBy('role')
            ->get()
            ->map(fn (User $roleCount): array => [
                'label' => ucfirst((string) $roleCount->role),
                'value' => (int) $roleCount->getAttribute('users_count'),
            ])
            ->all();

        return array_values($chart);
    }

    /**
     * @return Builder<Card>
     */
    private function companyCardsQuery(User $user): Builder
    {
        return Card::query()
            ->where('cards.is_restricted', false)
            ->whereHas('list.board', fn (Builder $query): Builder => $query->where('is_restricted', false))
            ->whereHas('list.board.workspace', fn (Builder $query): Builder => $query
                ->where('company_id', $user->company_id)
                ->where('is_restricted', false));
    }
}
