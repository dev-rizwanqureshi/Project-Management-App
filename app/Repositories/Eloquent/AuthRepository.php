<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Repositories\Contracts\CompanyMembershipRepositoryInterface;
use App\Repositories\Support\GeneratesCompanySlugs;
use App\Services\RolePermissionDefaults;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthRepository implements AuthRepositoryInterface
{
    use GeneratesCompanySlugs;

    public function __construct(
        private readonly RolePermissionDefaults $rolePermissionDefaults,
        private readonly CompanyMembershipRepositoryInterface $companyMembershipRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, company: Company}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $company = Company::create([
                'name' => (string) $data['company_name'],
                'slug' => $this->uniqueCompanySlug((string) $data['company_name']),
                'email' => (string) $data['company_email'],
            ]);

            $user = User::create([
                'name' => (string) $data['name'],
                'email' => (string) $data['email'],
                'password' => Hash::make((string) $data['password']),
                'role' => 'owner',
            ]);

            $roles = $this->rolePermissionDefaults->ensureForCompany($company, $user);
            $this->companyMembershipRepository->joinCompany($user, $company, 'owner', $roles->get('owner'));

            return [
                'user' => $user->refresh()->load('company', 'roleDefinition.permissions', 'activeCompanyMembership'),
                'company' => $company,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function attemptLogin(array $credentials): bool
    {
        $remember = (bool) ($credentials['remember'] ?? false);

        unset($credentials['remember']);

        return Auth::attempt([
            'email' => (string) $credentials['email'],
            'password' => (string) $credentials['password'],
        ], $remember);
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
