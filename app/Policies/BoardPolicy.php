<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

class BoardPolicy
{
    public function view(User $user, Board $board): bool
    {
        $board->loadMissing('workspace');

        return $this->activeBoardInCompany($user, $board)
            && $user->hasBoardAccess($board)
            && $user->hasPermission('boards.view');
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $this->sameCompany($user, $workspace->company_id)
            && ! $workspace->is_restricted
            && $user->hasWorkspaceAccess($workspace)
            && $user->hasPermission('boards.manage');
    }

    private function activeBoardInCompany(User $user, Board $board): bool
    {
        return $board->workspace
            && $this->sameCompany($user, $board->workspace->company_id)
            && ! $board->is_restricted
            && ! $board->is_archived
            && ! $board->workspace->is_restricted;
    }

    private function sameCompany(User $user, int $companyId): bool
    {
        return $user->company_id === $companyId;
    }
}
