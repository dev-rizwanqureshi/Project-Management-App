<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\Board;
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
            'overview' => $this->overview($user),
            'stats' => $canViewAnalytics ? $this->stats($user) : [],
            'ticketChart' => $this->ticketChart($user),
            'roleChart' => $canViewAnalytics ? $this->roleChart($user) : [],
            'canViewAnalytics' => $canViewAnalytics,
            'canManageRoles' => $user->hasPermission('roles.manage'),
        ];
    }

    /**
     * @return array{
     *     on_track_percent: int,
     *     open_tasks: int,
     *     open_tasks_percent: int,
     *     completed_tasks: int,
     *     due_this_week: int,
     *     due_this_week_percent: int,
     *     total_tasks: int,
     *     workspaces: int,
     *     boards: int,
     *     people: int
     * }
     */
    private function overview(User $user): array
    {
        $activeCards = $this->accessibleCardsQuery($user)
            ->where('cards.is_archived', false);
        $openTasks = (clone $activeCards)
            ->where('cards.is_completed', false)
            ->count();
        $completedTasks = (clone $activeCards)
            ->where('cards.is_completed', true)
            ->count();
        $totalTasks = $openTasks + $completedTasks;
        $dueThisWeek = (clone $activeCards)
            ->where('cards.is_completed', false)
            ->whereBetween('cards.due_date', [
                now()->startOfWeek()->startOfDay(),
                now()->endOfWeek()->endOfDay(),
            ])
            ->count();

        return [
            'on_track_percent' => $totalTasks > 0
                ? (int) round(($completedTasks / $totalTasks) * 100)
                : 0,
            'open_tasks' => $openTasks,
            'open_tasks_percent' => $totalTasks > 0
                ? (int) round(($openTasks / $totalTasks) * 100)
                : 0,
            'completed_tasks' => $completedTasks,
            'due_this_week' => $dueThisWeek,
            'due_this_week_percent' => $openTasks > 0
                ? (int) round(($dueThisWeek / $openTasks) * 100)
                : 0,
            'total_tasks' => $totalTasks,
            'workspaces' => $this->accessibleWorkspacesQuery($user)->count(),
            'boards' => $this->accessibleBoardsQuery($user)->count(),
            'people' => $user->hasPermission('users.view')
                ? User::query()
                    ->where('company_id', $user->company_id)
                    ->where('is_restricted', false)
                    ->count()
                : 0,
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
                'value' => $this->accessibleCardsQuery($user)->count(),
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
                'value' => (clone $this->accessibleCardsQuery($user))
                    ->where('is_completed', false)
                    ->where('is_archived', false)
                    ->count(),
            ],
            [
                'label' => 'Completed',
                'value' => (clone $this->accessibleCardsQuery($user))
                    ->where('is_completed', true)
                    ->where('is_archived', false)
                    ->count(),
            ],
            [
                'label' => 'Archived',
                'value' => (clone $this->accessibleCardsQuery($user))
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
    private function accessibleCardsQuery(User $user): Builder
    {
        $hasCompanyWideAccess = $user->hasCompanyWideAccess();

        return Card::query()
            ->where('cards.is_restricted', false)
            ->whereHas('list', fn (Builder $query): Builder => $query->where('is_archived', false))
            ->whereHas('list.board', fn (Builder $query): Builder => $query
                ->where('is_restricted', false)
                ->where('is_archived', false)
                ->when(! $hasCompanyWideAccess, fn (Builder $accessQuery): Builder => $accessQuery
                    ->where(fn (Builder $membershipQuery): Builder => $membershipQuery
                        ->whereHas('users', fn (Builder $usersQuery): Builder => $usersQuery->whereKey($user->id))
                        ->orWhereHas('workspace.users', fn (Builder $usersQuery): Builder => $usersQuery->whereKey($user->id)))))
            ->whereHas('list.board.workspace', fn (Builder $query): Builder => $query
                ->where('company_id', $user->company_id)
                ->where('is_restricted', false));
    }

    /**
     * @return Builder<Workspace>
     */
    private function accessibleWorkspacesQuery(User $user): Builder
    {
        $hasCompanyWideAccess = $user->hasCompanyWideAccess();

        return Workspace::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('is_restricted', false)
            ->when(! $hasCompanyWideAccess, fn (Builder $query): Builder => $query
                ->where(fn (Builder $accessQuery): Builder => $accessQuery
                    ->whereHas('users', fn (Builder $usersQuery): Builder => $usersQuery->whereKey($user->id))
                    ->orWhereHas('boards.users', fn (Builder $usersQuery): Builder => $usersQuery->whereKey($user->id))));
    }

    /**
     * @return Builder<Board>
     */
    private function accessibleBoardsQuery(User $user): Builder
    {
        $hasCompanyWideAccess = $user->hasCompanyWideAccess();

        return Board::query()
            ->where('is_restricted', false)
            ->where('is_archived', false)
            ->whereHas('workspace', fn (Builder $query): Builder => $query
                ->where('company_id', $user->company_id)
                ->where('is_restricted', false))
            ->when(! $hasCompanyWideAccess, fn (Builder $query): Builder => $query
                ->where(fn (Builder $accessQuery): Builder => $accessQuery
                    ->whereHas('users', fn (Builder $usersQuery): Builder => $usersQuery->whereKey($user->id))
                    ->orWhereHas('workspace.users', fn (Builder $usersQuery): Builder => $usersQuery->whereKey($user->id))));
    }
}
