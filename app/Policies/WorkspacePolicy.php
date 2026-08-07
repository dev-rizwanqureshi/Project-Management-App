<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $this->sameCompany($user, $workspace->company_id)
            && ! $workspace->is_restricted
            && $user->hasWorkspaceAccess($workspace)
            && $user->hasPermission('workspaces.view');
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null
            && $user->hasCompanyWideAccess()
            && $user->hasPermission('workspaces.manage');
    }

    private function sameCompany(User $user, int $companyId): bool
    {
        return $user->company_id === $companyId;
    }
}
