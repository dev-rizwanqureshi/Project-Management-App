<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use App\Repositories\Contracts\CompanyMembershipRepositoryInterface;
use App\Repositories\Contracts\CompanySetupRepositoryInterface;
use App\Repositories\Support\GeneratesCompanySlugs;
use App\Repositories\Support\NormalizesNullableStrings;
use App\Services\RolePermissionDefaults;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanySetupRepository implements CompanySetupRepositoryInterface
{
    use GeneratesCompanySlugs;
    use NormalizesNullableStrings;

    public function __construct(
        private readonly CompanyMembershipRepositoryInterface $companyMembershipRepository,
        private readonly RolePermissionDefaults $rolePermissionDefaults,
    ) {}

    public function userHasActiveCompany(User $user): bool
    {
        if ($user->company_id) {
            return true;
        }

        return CompanyUser::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUser(User $user, array $data): Company
    {
        return DB::transaction(function () use ($user, $data): Company {
            if ($this->userHasActiveCompany($user)) {
                throw ValidationException::withMessages([
                    'name' => 'You already have an active company.',
                ]);
            }

            $company = Company::query()->create([
                'name' => (string) $data['name'],
                'slug' => $this->uniqueCompanySlug((string) $data['name']),
                'email' => (string) $data['email'],
                'phone' => $this->nullableString($data, 'phone'),
                'website' => $this->nullableString($data, 'website'),
                'industry' => $this->nullableString($data, 'industry'),
                'team_size' => $this->nullableString($data, 'team_size'),
                'address_line' => $this->nullableString($data, 'address_line'),
                'city' => $this->nullableString($data, 'city'),
                'state' => $this->nullableString($data, 'state'),
                'country' => $this->nullableString($data, 'country'),
                'postal_code' => $this->nullableString($data, 'postal_code'),
                'timezone' => $this->nullableString($data, 'timezone'),
                'description' => $this->nullableString($data, 'description'),
                'trial_ends_at' => now()->addDays(14),
            ]);

            $roles = $this->rolePermissionDefaults->ensureForCompany($company, $user);
            $this->companyMembershipRepository->joinCompany($user, $company, 'owner', $roles->get('owner'));

            return $company->refresh();
        });
    }
}
