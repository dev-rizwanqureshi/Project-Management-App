<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\Board;
use App\Models\Card;
use App\Models\Company;
use App\Models\Workspace;
use App\Repositories\Contracts\AdminPlatformListingRepositoryInterface;
use App\Repositories\Support\BuildsListingPayloads;
use App\Repositories\Support\BuildsProjectListingPayloads;
use Illuminate\Http\Request;

class AdminPlatformListingRepository implements AdminPlatformListingRepositoryInterface
{
    use BuildsListingPayloads;
    use BuildsProjectListingPayloads;

    /**
     * @return array<string, mixed>
     */
    public function companies(Admin $admin, Request $request): array
    {
        $filters = $this->filters($request);
        $canRestrictCompanies = $admin->hasPermission('admin.companies.restrict');
        $filters['restriction'] = $this->restrictionFilter($request, $canRestrictCompanies);
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
            ->withCount(['users', 'workspaces'])
            ->selectSub($this->companyBoardsCountSubquery(), 'boards_count')
            ->selectSub($this->companyTicketsCountSubquery(), 'tickets_count');

        $this->applyRestrictionFilter($query, 'companies', $filters['restriction']);
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
            'can' => [
                'restrict_companies' => $canRestrictCompanies,
                'view_users' => $admin->hasPermission('admin.users.view'),
                'view_workspaces' => $admin->hasPermission('admin.workspaces.view'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function workspaces(Admin $admin, Request $request): array
    {
        $filters = $this->filters($request);
        $canRestrictWorkspaces = $admin->hasPermission('admin.workspaces.restrict');
        $filters['restriction'] = $this->restrictionFilter($request, $canRestrictWorkspaces);
        $filters['company_id'] = $this->idFilter($request, 'company_id');
        $filters['user_id'] = $this->idFilter($request, 'user_id');
        $sort = $this->sort($request, [
            'name',
            'company_name',
            'creator_name',
            'boards_count',
            'tickets_count',
            'users_count',
            'created_at',
            'updated_at',
        ], 'created_at', 'desc');

        $query = Workspace::query()
            ->select('workspaces.*')
            ->leftJoin('companies', 'companies.id', '=', 'workspaces.company_id')
            ->leftJoin('users as creators', 'creators.id', '=', 'workspaces.created_by')
            ->with(['company:id,name,slug', 'creator:id,name,email'])
            ->withCount('users')
            ->selectSub($this->workspaceBoardsCountSubquery(), 'boards_count')
            ->selectSub($this->workspaceTicketsCountSubquery(), 'tickets_count');

        $this->applyRestrictionFilter($query, 'workspaces', $filters['restriction']);

        if ($filters['company_id']) {
            $query->where('workspaces.company_id', $filters['company_id']);
        }

        if ($filters['user_id']) {
            $query->where('workspaces.created_by', $filters['user_id']);
        }

        $this->searchColumns($query, $filters['search'], [
            'workspaces.name',
            'workspaces.slug',
            'workspaces.description',
            'companies.name',
            'creators.name',
            'creators.email',
        ]);
        $this->orderByColumns($query, $sort, [
            'name' => 'workspaces.name',
            'company_name' => 'companies.name',
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
            'can' => [
                'restrict_workspaces' => $canRestrictWorkspaces,
                'view_boards' => $admin->hasPermission('admin.boards.view'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function boards(Admin $admin, Request $request): array
    {
        $filters = $this->filters($request);
        $canRestrictBoards = $admin->hasPermission('admin.boards.restrict');
        $filters['restriction'] = $this->restrictionFilter($request, $canRestrictBoards);
        $filters['company_id'] = $this->idFilter($request, 'company_id');
        $filters['workspace_id'] = $this->idFilter($request, 'workspace_id');
        $filters['user_id'] = $this->idFilter($request, 'user_id');
        $sort = $this->sort($request, [
            'name',
            'workspace_name',
            'company_name',
            'creator_name',
            'tickets_count',
            'users_count',
            'created_at',
            'updated_at',
        ], 'created_at', 'desc');

        $query = Board::query()
            ->select('boards.*')
            ->leftJoin('workspaces', 'workspaces.id', '=', 'boards.workspace_id')
            ->leftJoin('companies', 'companies.id', '=', 'workspaces.company_id')
            ->leftJoin('users as creators', 'creators.id', '=', 'boards.created_by')
            ->with(['creator:id,name,email', 'workspace:id,company_id,name', 'workspace.company:id,name'])
            ->withCount('users')
            ->selectSub($this->boardTicketsCountSubquery(), 'tickets_count');

        $this->applyRestrictionFilter($query, 'boards', $filters['restriction']);

        if ($filters['company_id']) {
            $query->where('workspaces.company_id', $filters['company_id']);
        }

        if ($filters['workspace_id']) {
            $query->where('boards.workspace_id', $filters['workspace_id']);
        }

        if ($filters['user_id']) {
            $query->where('boards.created_by', $filters['user_id']);
        }

        $this->searchColumns($query, $filters['search'], [
            'boards.name',
            'boards.description',
            'workspaces.name',
            'companies.name',
            'creators.name',
            'creators.email',
        ]);
        $this->orderByColumns($query, $sort, [
            'name' => 'boards.name',
            'workspace_name' => 'workspaces.name',
            'company_name' => 'companies.name',
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
            'filters' => $filters,
            'sort' => $sort,
            'can' => [
                'restrict_boards' => $canRestrictBoards,
                'view_tickets' => $admin->hasPermission('admin.cards.view'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cards(Admin $admin, Request $request): array
    {
        $filters = $this->filters($request);
        $canRestrictCards = $admin->hasPermission('admin.cards.restrict');
        $filters['restriction'] = $this->restrictionFilter($request, $canRestrictCards);
        $filters['company_id'] = $this->idFilter($request, 'company_id');
        $filters['workspace_id'] = $this->idFilter($request, 'workspace_id');
        $filters['board_id'] = $this->idFilter($request, 'board_id');
        $filters['user_id'] = $this->idFilter($request, 'user_id');
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
            ->leftJoin('companies', 'companies.id', '=', 'workspaces.company_id')
            ->leftJoin('users as creators', 'creators.id', '=', 'cards.created_by')
            ->with([
                'creator:id,name,email',
                'list:id,board_id,name',
                'list.board:id,workspace_id,name',
                'list.board.workspace:id,company_id,name',
                'list.board.workspace.company:id,name',
            ])
            ->withCount('assignees');

        $this->applyRestrictionFilter($query, 'cards', $filters['restriction']);

        if ($filters['company_id']) {
            $query->where('workspaces.company_id', $filters['company_id']);
        }

        if ($filters['workspace_id']) {
            $query->where('boards.workspace_id', $filters['workspace_id']);
        }

        if ($filters['board_id']) {
            $query->where('boards.id', $filters['board_id']);
        }

        if ($filters['user_id']) {
            $query->where('cards.created_by', $filters['user_id']);
        }

        $this->searchColumns($query, $filters['search'], [
            'cards.title',
            'cards.description',
            'lists.name',
            'boards.name',
            'workspaces.name',
            'companies.name',
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
            'can' => [
                'restrict_tickets' => $canRestrictCards,
                'view_boards' => $admin->hasPermission('admin.boards.view'),
            ],
        ];
    }

    public function setCompanyRestriction(Company $company, bool $restricted): void
    {
        $company->forceFill(['is_restricted' => $restricted])->save();
    }

    public function setWorkspaceRestriction(Workspace $workspace, bool $restricted): void
    {
        $workspace->forceFill(['is_restricted' => $restricted])->save();
    }

    public function setBoardRestriction(Board $board, bool $restricted): void
    {
        $board->forceFill(['is_restricted' => $restricted])->save();
    }

    public function setCardRestriction(Card $card, bool $restricted): void
    {
        $card->forceFill(['is_restricted' => $restricted])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function workspaceRow(Workspace $workspace): array
    {
        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'company' => $this->companySummary($workspace->company),
            'creator' => $this->userSummary($workspace->creator),
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
    private function boardRow(Board $board): array
    {
        return [
            'id' => $board->id,
            'name' => $board->name,
            'workspace' => $this->workspaceSummary($board->workspace),
            'company' => $this->companySummary($board->workspace?->company),
            'creator' => $this->userSummary($board->creator),
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
            'company' => $this->companySummary($workspace?->company),
            'creator' => $this->userSummary($card->creator),
            'assignees_count' => (int) $card->getAttribute('assignees_count'),
            'is_completed' => $card->is_completed,
            'is_archived' => $card->is_archived,
            'is_restricted' => $card->is_restricted,
            'created_at' => $this->date($card->created_at),
            'updated_at' => $this->date($card->updated_at),
        ];
    }
}
