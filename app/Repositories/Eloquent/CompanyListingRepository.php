<?php

namespace App\Repositories\Eloquent;

use App\Models\Board;
use App\Models\Card;
use App\Models\Company;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\CompanyListingRepositoryInterface;
use App\Repositories\Support\BuildsListingPayloads;
use App\Repositories\Support\BuildsProjectListingPayloads;
use Illuminate\Http\Request;

class CompanyListingRepository implements CompanyListingRepositoryInterface
{
    use BuildsListingPayloads;
    use BuildsProjectListingPayloads;

    /**
     * @return array<string, mixed>
     */
    public function companies(User $user, Request $request): array
    {
        $filters = $this->filters($request);
        $sort = $this->sort($request, [
            'name',
            'email',
            'users_count',
            'workspaces_count',
            'boards_count',
            'tickets_count',
            'created_at',
            'updated_at',
        ], 'name');

        $query = Company::query()
            ->select('companies.*')
            ->whereKey($user->company_id)
            ->where('companies.is_restricted', false)
            ->withCount(['users', 'workspaces'])
            ->selectSub($this->companyBoardsCountSubquery(), 'boards_count')
            ->selectSub($this->companyTicketsCountSubquery(), 'tickets_count');

        $this->searchColumns($query, $filters['search'], [
            'companies.name',
            'companies.slug',
            'companies.email',
        ]);

        $companies = $query
            ->orderBy($sort['field'], $sort['direction'])
            ->orderBy('companies.id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Company $company): array => $this->companyListingRow($company));

        return [
            'companies' => $this->paginatorPayload($companies),
            'filters' => $filters,
            'sort' => $sort,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function users(User $user, Request $request): array
    {
        $filters = $this->filters($request);
        $sort = $this->sort($request, [
            'name',
            'email',
            'role',
            'workspaces_count',
            'boards_count',
            'tickets_count',
            'created_at',
            'updated_at',
        ], 'name');

        $query = User::query()
            ->select('users.*')
            ->where('company_id', $user->company_id)
            ->where('users.is_restricted', false)
            ->with('company:id,name,slug')
            ->withCount([
                'createdWorkspaces as workspaces_count' => fn ($query) => $query
                    ->where('workspaces.company_id', $user->company_id),
                'createdBoards as boards_count' => fn ($query) => $query
                    ->whereHas(
                        'workspace',
                        fn ($workspaceQuery) => $workspaceQuery
                            ->where('company_id', $user->company_id),
                    ),
                'createdCards as tickets_count' => fn ($query) => $query
                    ->whereHas(
                        'list.board.workspace',
                        fn ($workspaceQuery) => $workspaceQuery
                            ->where('company_id', $user->company_id),
                    ),
            ]);

        $this->searchColumns($query, $filters['search'], [
            'users.name',
            'users.email',
            'users.role',
        ]);
        $this->orderByColumns($query, $sort, [
            'name' => 'users.name',
            'email' => 'users.email',
            'role' => 'users.role',
            'created_at' => 'users.created_at',
            'updated_at' => 'users.updated_at',
        ]);

        $users = $query
            ->orderBy('users.id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (User $companyUser): array => $this->userRow($companyUser));

        return [
            'users' => $this->paginatorPayload($users),
            'filters' => $filters,
            'sort' => $sort,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function workspaces(User $user, Request $request): array
    {
        $filters = $this->filters($request);
        $sort = $this->sort($request, [
            'name',
            'creator_name',
            'boards_count',
            'tickets_count',
            'users_count',
            'created_at',
            'updated_at',
        ], 'created_at', 'desc');

        $query = Workspace::query()
            ->select('workspaces.*')
            ->where('workspaces.company_id', $user->company_id)
            ->where('workspaces.is_restricted', false)
            ->leftJoin('users as creators', 'creators.id', '=', 'workspaces.created_by')
            ->with(['company:id,name,slug', 'creator:id,name,email'])
            ->withCount('users')
            ->selectSub($this->workspaceBoardsCountSubquery($user), 'boards_count')
            ->selectSub($this->workspaceTicketsCountSubquery($user), 'tickets_count');
        $this->applyWorkspaceAccess($query, $user, 'workspaces.id');

        $this->searchColumns($query, $filters['search'], [
            'workspaces.name',
            'workspaces.slug',
            'workspaces.description',
            'creators.name',
            'creators.email',
        ]);
        $this->orderByColumns($query, $sort, [
            'name' => 'workspaces.name',
            'creator_name' => 'creators.name',
            'created_at' => 'workspaces.created_at',
            'updated_at' => 'workspaces.updated_at',
        ]);

        $workspaces = $query
            ->orderBy('workspaces.id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Workspace $workspace): array => $this->workspaceRow($workspace));

        return [
            'workspaces' => $this->paginatorPayload($workspaces),
            'filters' => $filters,
            'sort' => $sort,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function boards(User $user, Request $request): array
    {
        $filters = $this->filters($request);
        $workspaceId = max(0, $request->integer('workspace_id'));
        $sort = $this->sort($request, [
            'name',
            'workspace_name',
            'creator_name',
            'tickets_count',
            'users_count',
            'created_at',
            'updated_at',
        ], 'created_at', 'desc');

        $query = Board::query()
            ->select('boards.*')
            ->leftJoin('workspaces', 'workspaces.id', '=', 'boards.workspace_id')
            ->leftJoin('users as creators', 'creators.id', '=', 'boards.created_by')
            ->where('workspaces.company_id', $user->company_id)
            ->where('boards.is_restricted', false)
            ->with(['creator:id,name,email', 'workspace:id,company_id,name'])
            ->withCount('users')
            ->selectSub($this->boardTicketsCountSubquery(), 'tickets_count');
        $this->applyBoardAccess($query, $user, 'boards.id', 'boards.workspace_id');

        if ($workspaceId > 0) {
            $query->where('workspaces.id', $workspaceId);
        }

        $this->searchColumns($query, $filters['search'], [
            'boards.name',
            'boards.description',
            'workspaces.name',
            'creators.name',
            'creators.email',
        ]);
        $this->orderByColumns($query, $sort, [
            'name' => 'boards.name',
            'workspace_name' => 'workspaces.name',
            'creator_name' => 'creators.name',
            'created_at' => 'boards.created_at',
            'updated_at' => 'boards.updated_at',
        ]);

        $boards = $query
            ->orderBy('boards.id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Board $board): array => $this->boardRow($board));

        return [
            'boards' => $this->paginatorPayload($boards),
            'filters' => [...$filters, 'workspace_id' => $workspaceId],
            'sort' => $sort,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cards(User $user, Request $request): array
    {
        $filters = $this->filters($request);
        $sort = $this->sort($request, [
            'title',
            'list_name',
            'board_name',
            'workspace_name',
            'creator_name',
            'assignees_count',
            'is_completed',
            'is_archived',
            'created_at',
            'updated_at',
        ], 'created_at', 'desc');

        $query = Card::query()
            ->select('cards.*')
            ->leftJoin('lists', 'lists.id', '=', 'cards.list_id')
            ->leftJoin('boards', 'boards.id', '=', 'lists.board_id')
            ->leftJoin('workspaces', 'workspaces.id', '=', 'boards.workspace_id')
            ->leftJoin('users as creators', 'creators.id', '=', 'cards.created_by')
            ->where('workspaces.company_id', $user->company_id)
            ->where('cards.is_restricted', false)
            ->with([
                'creator:id,name,email',
                'list:id,board_id,name',
                'list.board:id,workspace_id,name',
                'list.board.workspace:id,company_id,name',
            ])
            ->withCount('assignees');
        $this->applyBoardAccess($query, $user, 'boards.id', 'workspaces.id');

        $this->searchColumns($query, $filters['search'], [
            'cards.title',
            'cards.description',
            'lists.name',
            'boards.name',
            'workspaces.name',
            'creators.name',
            'creators.email',
        ]);
        $this->orderByColumns($query, $sort, [
            'title' => 'cards.title',
            'list_name' => 'lists.name',
            'board_name' => 'boards.name',
            'workspace_name' => 'workspaces.name',
            'creator_name' => 'creators.name',
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

        return [
            'cards' => $this->paginatorPayload($cards),
            'filters' => $filters,
            'sort' => $sort,
        ];
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
            'workspaces_count' => (int) $user->getAttribute('workspaces_count'),
            'boards_count' => (int) $user->getAttribute('boards_count'),
            'tickets_count' => (int) $user->getAttribute('tickets_count'),
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
            'description' => $workspace->description,
            'color' => $workspace->color,
            'creator' => $this->userSummary($workspace->creator),
            'boards_count' => (int) $workspace->getAttribute('boards_count'),
            'tickets_count' => (int) $workspace->getAttribute('tickets_count'),
            'users_count' => (int) $workspace->getAttribute('users_count'),
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
            'description' => $board->description,
            'background' => $board->background,
            'workspace' => $this->workspaceSummary($board->workspace),
            'creator' => $this->userSummary($board->creator),
            'tickets_count' => (int) $board->getAttribute('tickets_count'),
            'users_count' => (int) $board->getAttribute('users_count'),
            'created_at' => $this->date($board->created_at),
            'updated_at' => $this->date($board->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cardRow(Card $card): array
    {
        $list = $card->list;
        $board = $list?->board;
        $workspace = $board?->workspace;

        return [
            'id' => $card->id,
            'title' => $card->title,
            'list_name' => $list?->name,
            'board' => $this->boardSummary($board),
            'workspace' => $this->workspaceSummary($workspace),
            'creator' => $this->userSummary($card->creator),
            'assignees_count' => (int) $card->getAttribute('assignees_count'),
            'is_completed' => $card->is_completed,
            'is_archived' => $card->is_archived,
            'created_at' => $this->date($card->created_at),
            'updated_at' => $this->date($card->updated_at),
        ];
    }

    private function applyWorkspaceAccess(mixed $query, User $user, string $workspaceColumn): void
    {
        if ($user->hasCompanyWideAccess()) {
            return;
        }

        $query->where(function ($accessQuery) use ($user, $workspaceColumn): void {
            $accessQuery
                ->whereExists(function ($membershipQuery) use ($user, $workspaceColumn): void {
                    $membershipQuery
                        ->selectRaw('1')
                        ->from('workspace_user')
                        ->whereColumn('workspace_user.workspace_id', $workspaceColumn)
                        ->where('workspace_user.user_id', $user->id);
                })
                ->orWhereExists(function ($membershipQuery) use ($user, $workspaceColumn): void {
                    $membershipQuery
                        ->selectRaw('1')
                        ->from('board_user')
                        ->join('boards as access_boards', 'access_boards.id', '=', 'board_user.board_id')
                        ->whereColumn('access_boards.workspace_id', $workspaceColumn)
                        ->where('board_user.user_id', $user->id);
                });
        });
    }

    private function applyBoardAccess(
        mixed $query,
        User $user,
        string $boardColumn,
        string $workspaceColumn,
    ): void {
        if ($user->hasCompanyWideAccess()) {
            return;
        }

        $query->where(function ($accessQuery) use ($user, $boardColumn, $workspaceColumn): void {
            $accessQuery
                ->whereExists(function ($membershipQuery) use ($user, $boardColumn): void {
                    $membershipQuery
                        ->selectRaw('1')
                        ->from('board_user')
                        ->whereColumn('board_user.board_id', $boardColumn)
                        ->where('board_user.user_id', $user->id);
                })
                ->orWhereExists(function ($membershipQuery) use ($user, $workspaceColumn): void {
                    $membershipQuery
                        ->selectRaw('1')
                        ->from('workspace_user')
                        ->whereColumn('workspace_user.workspace_id', $workspaceColumn)
                        ->where('workspace_user.user_id', $user->id);
                });
        });
    }
}
