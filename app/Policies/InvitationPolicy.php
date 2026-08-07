<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class InvitationPolicy
{
    public function create(User $user, Company $company): bool
    {
        return $user->company_id === $company->id
            && $user->hasPermission('users.manage');
    }
}
