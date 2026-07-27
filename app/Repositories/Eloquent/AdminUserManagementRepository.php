<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\Board;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\AdminUserManagementRepositoryInterface;
use App\Repositories\Support\BuildsListingPayloads;
use App\Repositories\Support\BuildsProjectListingPayloads;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserManagementRepository implements AdminUserManagementRepositoryInterface
{
    use BuildsListingPayloads;
    use BuildsProjectListingPayloads;

    /**
     * @return array<string, mixed>
     */
    public function indexPayload(Admin $admin, Request $request): array
    {
        $filters = $this->filters($request);
        $canRestrictUsers = $admin->hasPermission('admin.users.restrict');
        $filters['restriction'] = $this->restrictionFilter($request, $canRestrictUsers);
        $filters['company_id'] = $this->idFilter($request, 'company_id');
        $sort = $this->sort($request, [
            'name',
            'email',
            'company_name',
            'role',
            'workspaces_count',
            'boards_count',
            'tickets_count',
            'created_at',
            'updated_at',
        ], 'name');

        $query = User::query()
            ->select('users.*')
            ->leftJoin('companies', 'companies.id', '=', 'users.company_id')
            ->with('company:id,name,slug')
            ->withCount([
                'createdWorkspaces as workspaces_count',
                'createdBoards as boards_count',
                'createdCards as tickets_count',
            ]);

        $this->applyRestrictionFilter($query, 'users', $filters['restriction']);

        if ($filters['company_id']) {
            $query->where('users.company_id', $filters['company_id']);
        }

        $this->searchColumns($query, $filters['search'], [
            'users.name',
            'users.email',
            'users.role',
            'companies.name',
        ]);
        $this->orderByColumns($query, $sort, [
            'name' => 'users.name',
            'email' => 'users.email',
            'company_name' => 'companies.name',
            'role' => 'users.role',
            'created_at' => 'users.created_at',
            'updated_at' => 'users.updated_at',
        ]);

        $users = $query
            ->orderBy('users.id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (User $user): array => $this->userRow($user));

        return [
            'users' => $this->paginatorPayload($users),
            'filters' => $filters,
            'sort' => $sort,
            'can' => [
                'restrict_users' => $canRestrictUsers,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function userPage(Admin $admin, User $user, Request $request): array
    {
        $user->load('company:id,name,slug');

        $filters = $this->filters($request);
        $sort = $this->sort($request, [
            'name',
            'boards_count',
            'tickets_count',
            'users_count',
            'created_at',
            'updated_at',
        ], 'created_at', 'desc');

        $canViewWorkspaces = $admin->hasPermission('admin.workspaces.view');
        $canRestrictWorkspaces = $admin->hasPermission('admin.workspaces.restrict');
        $filters['restriction'] = $this->restrictionFilter($request, $canRestrictWorkspaces);

        return [
            'user' => $this->userPayload($user),
            'stats' => $this->userStats($user),
            'workspaces' => $canViewWorkspaces
                ? $this->workspacesCreatedByUser($user, $filters, $sort)
                : $this->emptyPaginatorPayload($filters['per_page']),
            'filters' => $filters,
            'sort' => $sort,
            'can' => [
                'view_workspaces' => $canViewWorkspaces,
                'restrict_users' => $admin->hasPermission('admin.users.restrict'),
                'restrict_workspaces' => $canRestrictWorkspaces,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function workspacePage(Admin $admin, int $workspaceId, Request $request): array
    {
        $workspace = Workspace::withoutGlobalScopes()
            ->with(['company:id,name,slug', 'creator:id,name,email'])
            ->findOrFail($workspaceId);

        $filters = $this->filters($request);
        $sort = $this->sort($request, [
            'name',
            'tickets_count',
            'users_count',
            'created_at',
            'updated_at',
        ], 'created_at', 'desc');

        $canViewBoards = $admin->hasPermission('admin.boards.view');
        $canRestrictBoards = $admin->hasPermission('admin.boards.restrict');
        $filters['restriction'] = $this->restrictionFilter($request, $canRestrictBoards);

        return [
            'workspace' => $this->workspacePayload($workspace),
            'stats' => $this->workspaceStats($workspace),
            'boards' => $canViewBoards
                ? $this->boardsForWorkspace($workspace, $filters, $sort)
                : $this->emptyPaginatorPayload($filters['per_page']),
            'filters' => $filters,
            'sort' => $sort,
            'can' => [
                'view_boards' => $canViewBoards,
                'restrict_workspaces' => $admin->hasPermission('admin.workspaces.restrict'),
                'restrict_boards' => $canRestrictBoards,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function boardPage(Admin $admin, int $boardId, Request $request): array
    {
        $board = Board::query()
            ->with(['creator:id,name,email'])
            ->findOrFail($boardId);

        $workspace = Workspace::withoutGlobalScopes()
            ->with('company:id,name,slug')
            ->findOrFail($board->workspace_id);

        $board->setRelation('workspace', $workspace);

        $filters = $this->filters($request);
        $sort = $this->sort($request, [
            'title',
            'assignees_count',
            'is_completed',
            'is_archived',
            'created_at',
            'updated_at',
        ], 'created_at', 'desc');

        $canViewCards = $admin->hasPermission('admin.cards.view');
        $canRestrictCards = $admin->hasPermission('admin.cards.restrict');
        $filters['restriction'] = $this->restrictionFilter($request, $canRestrictCards);

        return [
            'board' => $this->boardPayload($board),
            'stats' => $this->boardStats($board),
            'cards' => $canViewCards
                ? $this->cardsForBoard($board, $filters, $sort)
                : $this->emptyPaginatorPayload($filters['per_page']),
            'filters' => $filters,
            'sort' => $sort,
            'can' => [
                'view_tickets' => $canViewCards,
                'restrict_boards' => $admin->hasPermission('admin.boards.restrict'),
                'restrict_tickets' => $canRestrictCards,
            ],
        ];
    }

    public function setUserRestriction(User $user, bool $restricted): void
    {
        $user->forceFill(['is_restricted' => $restricted])->save();
    }

    /**
     * @param  array{search: string, per_page: int, restriction: 'active'|'restricted'|'all'}  $filters
     * @param  array{field: string, direction: 'asc'|'desc'}  $sort
     * @return array<string, mixed>
     */
    private function workspacesCreatedByUser(User $user, array $filters, array $sort): array
    {
        $query = Workspace::withoutGlobalScopes()
            ->select('workspaces.*')
            ->where('created_by', $user->id)
            ->withCount('users')
            ->selectSub($this->workspaceBoardsCountSubquery(), 'boards_count')
            ->selectSub($this->workspaceTicketsCountSubquery(), 'tickets_count');

        $this->applyRestrictionFilter($query, 'workspaces', $filters['restriction']);
        $this->searchColumns($query, $filters['search'], [
            'workspaces.name',
            'workspaces.slug',
            'workspaces.description',
        ]);
        $this->orderByColumns($query, $sort, [
            'name' => 'workspaces.name',
            'created_at' => 'workspaces.created_at',
            'updated_at' => 'workspaces.updated_at',
        ]);

        $workspaces = $query
            ->orderBy('workspaces.id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Workspace $workspace): array => $this->workspaceRow($workspace));

        return $this->paginatorPayload($workspaces);
    }

    /**
     * @param  array{search: string, per_page: int, restriction: 'active'|'restricted'|'all'}  $filters
     * @param  array{field: string, direction: 'asc'|'desc'}  $sort
     * @return array<string, mixed>
     */
    private function boardsForWorkspace(Workspace $workspace, array $filters, array $sort): array
    {
        $query = Board::query()
            ->select('boards.*')
            ->where('workspace_id', $workspace->id)
            ->withCount('users')
            ->selectSub($this->boardTicketsCountSubquery(), 'tickets_count');

        $this->applyRestrictionFilter($query, 'boards', $filters['restriction']);
        $this->searchColumns($query, $filters['search'], [
            'boards.name',
            'boards.description',
        ]);
        $this->orderByColumns($query, $sort, [
            'name' => 'boards.name',
            'created_at' => 'boards.created_at',
            'updated_at' => 'boards.updated_at',
        ]);

        $boards = $query
            ->orderBy('boards.id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Board $board): array => $this->boardRow($board));

        return $this->paginatorPayload($boards);
    }

    /**
     * @param  array{search: string, per_page: int, restriction: 'active'|'restricted'|'all'}  $filters
     * @param  array{field: string, direction: 'asc'|'desc'}  $sort
     * @return array<string, mixed>
     */
    private function cardsForBoard(Board $board, array $filters, array $sort): array
    {
        $query = Card::query()
            ->select('cards.*')
            ->with(['creator:id,name,email', 'list:id,board_id,name'])
            ->withCount('assignees')
            ->whereHas('list', fn (Builder $query): Builder => $query->where('board_id', $board->id));

        $this->applyRestrictionFilter($query, 'cards', $filters['restriction']);
        $this->searchCards($query, $filters['search']);
        $this->orderByColumns($query, $sort, [
            'title' => 'cards.title',
            'is_completed' => 'cards.is_completed',
            'is_archived' => 'cards.is_archived',
            'created_at' => 'cards.created_at',
            'updated_at' => 'cards.updated_at',
        ]);

        $cards = $query
            ->orderBy('cards.id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Card $card): array => $this->cardRow($card));

        return $this->paginatorPayload($cards);
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function userStats(User $user): array
    {
        return [
            ['label' => 'Workspaces built', 'value' => Workspace::withoutGlobalScopes()->where('created_by', $user->id)->where('is_restricted', false)->count()],
            ['label' => 'Boards built', 'value' => Board::query()->where('created_by', $user->id)->where('is_restricted', false)->count()],
            ['label' => 'Tickets created', 'value' => Card::query()->where('created_by', $user->id)->where('is_restricted', false)->count()],
            ['label' => 'Tickets assigned', 'value' => $user->assignedCards()->where('cards.is_restricted', false)->count()],
        ];
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function workspaceStats(Workspace $workspace): array
    {
        return [
            ['label' => 'Boards', 'value' => Board::query()->where('workspace_id', $workspace->id)->where('is_restricted', false)->count()],
            ['label' => 'Tickets', 'value' => $this->ticketsForWorkspace($workspace)->count()],
            ['label' => 'Users', 'value' => $workspace->users()->count()],
        ];
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function boardStats(Board $board): array
    {
        return [
            ['label' => 'Tickets', 'value' => $this->ticketsForBoard($board)->count()],
            ['label' => 'Assigned users', 'value' => $this->assignedUsersCountForBoard($board)],
            ['label' => 'Board users', 'value' => $board->users()->count()],
        ];
    }

    /**
     * @param  Builder<Card>  $query
     */
    private function searchCards(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('cards.title', 'like', "%{$search}%")
                ->orWhere('cards.description', 'like', "%{$search}%")
                ->orWhereHas('creator', fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function userRow(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'company' => $this->companySummary($user->company),
            'workspaces_count' => (int) $user->getAttribute('workspaces_count'),
            'boards_count' => (int) $user->getAttribute('boards_count'),
            'tickets_count' => (int) $user->getAttribute('tickets_count'),
            'is_restricted' => $user->is_restricted,
            'created_at' => $this->date($user->created_at),
            'updated_at' => $this->date($user->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'company' => $this->companySummary($user->company),
            'is_restricted' => $user->is_restricted,
            'created_at' => $this->date($user->created_at),
            'updated_at' => $this->date($user->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function workspaceRow(Workspace $workspace): array
    {
        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'boards_count' => (int) $workspace->getAttribute('boards_count'),
            'tickets_count' => (int) $workspace->getAttribute('tickets_count'),
            'users_count' => (int) $workspace->getAttribute('users_count'),
            'is_restricted' => $workspace->is_restricted,
            'created_at' => $this->date($workspace->created_at),
            'updated_at' => $this->date($workspace->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function workspacePayload(Workspace $workspace): array
    {
        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'company' => $this->companySummary($workspace->company),
            'creator' => $this->userSummary($workspace->creator),
            'created_at' => $this->date($workspace->created_at),
            'updated_at' => $this->date($workspace->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function boardRow(Board $board): array
    {
        return [
            'id' => $board->id,
            'name' => $board->name,
            'tickets_count' => (int) $board->getAttribute('tickets_count'),
            'users_count' => (int) $board->getAttribute('users_count'),
            'is_restricted' => $board->is_restricted,
            'created_at' => $this->date($board->created_at),
            'updated_at' => $this->date($board->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function boardPayload(Board $board): array
    {
        return [
            'id' => $board->id,
            'name' => $board->name,
            'workspace' => $this->workspaceSummary($board->workspace),
            'creator' => $this->userSummary($board->creator),
            'created_at' => $this->date($board->created_at),
            'updated_at' => $this->date($board->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cardRow(Card $card): array
    {
        return [
            'id' => $card->id,
            'title' => $card->title,
            'list_name' => $card->list?->name,
            'is_completed' => $card->is_completed,
            'is_archived' => $card->is_archived,
            'creator' => $this->userSummary($card->creator),
            'assignees_count' => (int) $card->getAttribute('assignees_count'),
            'is_restricted' => $card->is_restricted,
            'created_at' => $this->date($card->created_at),
            'updated_at' => $this->date($card->updated_at),
        ];
    }

    private function assignedUsersCountForBoard(Board $board): int
    {
        return DB::table('card_user')
            ->join('cards', 'cards.id', '=', 'card_user.card_id')
            ->join('lists', 'lists.id', '=', 'cards.list_id')
            ->where('lists.board_id', $board->id)
            ->where('cards.is_restricted', false)
            ->whereNull('cards.deleted_at')
            ->distinct('card_user.user_id')
            ->count('card_user.user_id');
    }

    /**
     * @return Builder<Card>
     */
    private function ticketsForWorkspace(Workspace $workspace): Builder
    {
        return Card::query()
            ->where('cards.is_restricted', false)
            ->whereHas('list.board', fn (Builder $query): Builder => $query
                ->where('workspace_id', $workspace->id)
                ->where('is_restricted', false));
    }

    /**
     * @return Builder<Card>
     */
    private function ticketsForBoard(Board $board): Builder
    {
        return Card::query()
            ->where('cards.is_restricted', false)
            ->whereHas('list', fn (Builder $query): Builder => $query->where('board_id', $board->id));
    }
}
