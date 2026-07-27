<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Role;
use App\Models\User;

interface CompanyMembershipRepositoryInterface
{
    public function activeMembership(User $user): ?CompanyUser;

    public function joinCompany(User $user, Company $company, string $role, ?Role $roleDefinition = null): CompanyUser;

    public function leaveActiveCompany(User $user): void;
}
