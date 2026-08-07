<?php

namespace App\Repositories\Support;

use App\Models\Board;
use App\Models\Company;
use App\Models\User;
use App\Models\Workspace;
use Closure;

trait BuildsProjectListingPayloads
{
    /**
     * @return array<string, mixed>
     */
    protected function companyListingRow(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'users_count' => (int) $company->getAttribute('users_count'),
            'workspaces_count' => (int) $company->getAttribute('workspaces_count'),
            'boards_count' => (int) $company->getAttribute('boards_count'),
            'tickets_count' => (int) $company->getAttribute('tickets_count'),
            'is_restricted' => $company->is_restricted,
            'created_at' => $this->date($company->created_at),
            'updated_at' => $this->date($company->updated_at),
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    protected function companySummary(?Company $company): ?array
    {
        if ($company === null) {
            return null;
        }

        return [
            'id' => $company->id,
            'name' => $company->name,
        ];
    }

    /**
     * @return array{id: int, name: string, email: string}|null
     */
    protected function userSummary(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    protected function workspaceSummary(?Workspace $workspace): ?array
    {
        if ($workspace === null) {
            return null;
        }

        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    protected function boardSummary(?Board $board): ?array
    {
        if ($board === null) {
            return null;
        }

        return [
            'id' => $board->id,
            'name' => $board->name,
        ];
    }

    protected function companyBoardsCountSubquery(): Closure
    {
        return fn ($query) => $query
            ->from('boards')
            ->join('workspaces', 'workspaces.id', '=', 'boards.workspace_id')
            ->selectRaw('count(*)')
            ->whereColumn('workspaces.company_id', 'companies.id')
            ->where('boards.is_restricted', false)
            ->where('workspaces.is_restricted', false)
            ->whereNull('boards.deleted_at')
            ->whereNull('workspaces.deleted_at');
    }

    protected function companyTicketsCountSubquery(): Closure
    {
        return fn ($query) => $query
            ->from('cards')
            ->join('lists', 'lists.id', '=', 'cards.list_id')
            ->join('boards', 'boards.id', '=', 'lists.board_id')
            ->join('workspaces', 'workspaces.id', '=', 'boards.workspace_id')
            ->selectRaw('count(*)')
            ->whereColumn('workspaces.company_id', 'companies.id')
            ->where('cards.is_restricted', false)
            ->where('boards.is_restricted', false)
            ->where('workspaces.is_restricted', false)
            ->whereNull('cards.deleted_at')
            ->whereNull('boards.deleted_at')
            ->whereNull('workspaces.deleted_at');
    }

    protected function workspaceBoardsCountSubquery(?User $user = null): Closure
    {
        return function ($query) use ($user) {
            $query
                ->from('boards')
                ->selectRaw('count(*)')
                ->whereColumn('boards.workspace_id', 'workspaces.id')
                ->where('boards.is_restricted', false)
                ->whereNull('boards.deleted_at');

            if ($user !== null && ! $user->hasCompanyWideAccess()) {
                $query->where(function ($accessQuery) use ($user): void {
                    $accessQuery
                        ->whereExists(function ($membershipQuery) use ($user): void {
                            $membershipQuery
                                ->selectRaw('1')
                                ->from('workspace_user')
                                ->whereColumn('workspace_user.workspace_id', 'workspaces.id')
                                ->where('workspace_user.user_id', $user->id);
                        })
                        ->orWhereExists(function ($membershipQuery) use ($user): void {
                            $membershipQuery
                                ->selectRaw('1')
                                ->from('board_user')
                                ->whereColumn('board_user.board_id', 'boards.id')
                                ->where('board_user.user_id', $user->id);
                        });
                });
            }

            return $query;
        };
    }

    protected function workspaceTicketsCountSubquery(?User $user = null): Closure
    {
        return function ($query) use ($user) {
            $query
                ->from('cards')
                ->join('lists', 'lists.id', '=', 'cards.list_id')
                ->join('boards', 'boards.id', '=', 'lists.board_id')
                ->selectRaw('count(*)')
                ->whereColumn('boards.workspace_id', 'workspaces.id')
                ->where('cards.is_restricted', false)
                ->where('boards.is_restricted', false)
                ->whereNull('cards.deleted_at')
                ->whereNull('boards.deleted_at');

            if ($user !== null && ! $user->hasCompanyWideAccess()) {
                $query->where(function ($accessQuery) use ($user): void {
                    $accessQuery
                        ->whereExists(function ($membershipQuery) use ($user): void {
                            $membershipQuery
                                ->selectRaw('1')
                                ->from('workspace_user')
                                ->whereColumn('workspace_user.workspace_id', 'workspaces.id')
                                ->where('workspace_user.user_id', $user->id);
                        })
                        ->orWhereExists(function ($membershipQuery) use ($user): void {
                            $membershipQuery
                                ->selectRaw('1')
                                ->from('board_user')
                                ->whereColumn('board_user.board_id', 'boards.id')
                                ->where('board_user.user_id', $user->id);
                        });
                });
            }

            return $query;
        };
    }

    protected function boardTicketsCountSubquery(): Closure
    {
        return fn ($query) => $query
            ->from('cards')
            ->join('lists', 'lists.id', '=', 'cards.list_id')
            ->selectRaw('count(*)')
            ->whereColumn('lists.board_id', 'boards.id')
            ->where('cards.is_restricted', false)
            ->whereNull('cards.deleted_at');
    }
}
