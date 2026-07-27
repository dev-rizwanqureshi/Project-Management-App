<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use App\Models\User;

interface CompanySetupRepositoryInterface
{
    public function userHasActiveCompany(User $user): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUser(User $user, array $data): Company;
}
