<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\CompanyMembershipRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyMembershipRepository implements CompanyMembershipRepositoryInterface
{
    public function activeMembership(User $user): ?CompanyUser
    {
        return CompanyUser::query()
            ->with(['company', 'roleDefinition.permissions'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
    }

    public function joinCompany(
        User $user,
        Company $company,
        string $role,
        ?Role $roleDefinition = null,
        bool $isCompanyWide = true,
    ): CompanyUser {
        return DB::transaction(function () use ($user, $company, $role, $roleDefinition, $isCompanyWide): CompanyUser {
            $lockedUser = $this->lockedUser($user);
            $activeMembership = $this->lockedActiveMembership($lockedUser);

            if ($activeMembership && $activeMembership->company_id !== $company->id) {
                throw ValidationException::withMessages([
                    'company' => 'Leave the current company before joining another company.',
                ]);
            }

            $roleDefinition ??= $this->roleDefinition($company, $role);

            $membership = CompanyUser::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'user_id' => $lockedUser->id,
                ],
                [
                    'role' => $role,
                    'role_id' => $roleDefinition?->id,
                    'status' => 'active',
                    'is_company_wide' => $isCompanyWide,
                    'joined_at' => now(),
                    'left_at' => null,
                ],
            );

            $lockedUser->forceFill([
                'company_id' => $company->id,
                'role' => $role,
                'role_id' => $roleDefinition?->id,
            ])->save();

            $lockedUser->setRelation('company', $company);
            $lockedUser->setRelation('roleDefinition', $roleDefinition);

            return $membership->load(['company', 'roleDefinition.permissions']);
        });
    }

    public function leaveActiveCompany(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $lockedUser = $this->lockedUser($user);
            $activeMembership = $this->lockedActiveMembership($lockedUser);

            if (! $activeMembership) {
                return;
            }

            $this->ensureUserCanLeave($lockedUser, $activeMembership);
            $this->removeCompanyAccess($lockedUser, $activeMembership->company_id);

            $activeMembership->forceFill([
                'status' => 'left',
                'left_at' => now(),
            ])->save();

            $lockedUser->forceFill([
                'company_id' => null,
                'role' => 'member',
                'role_id' => null,
            ])->save();
        });
    }

    private function lockedUser(User $user): User
    {
        return User::query()
            ->whereKey($user->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockedActiveMembership(User $user): ?CompanyUser
    {
        return CompanyUser::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();
    }

    private function roleDefinition(Company $company, string $role): ?Role
    {
        return Role::query()
            ->where('company_id', $company->id)
            ->where('slug', $role)
            ->first();
    }

    private function ensureUserCanLeave(User $user, CompanyUser $membership): void
    {
        if ($membership->role !== 'owner') {
            return;
        }

        $hasAnotherOwner = CompanyUser::query()
            ->where('company_id', $membership->company_id)
            ->where('status', 'active')
            ->where('role', 'owner')
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($hasAnotherOwner) {
            return;
        }

        throw ValidationException::withMessages([
            'company' => 'Transfer ownership before leaving this company.',
        ]);
    }

    private function removeCompanyAccess(User $user, int $companyId): void
    {
        DB::table('workspace_user')
            ->where('user_id', $user->id)
            ->whereIn('workspace_id', function ($query) use ($companyId): void {
                $query->select('id')
                    ->from('workspaces')
                    ->where('company_id', $companyId);
            })
            ->delete();

        DB::table('board_user')
            ->where('user_id', $user->id)
            ->whereIn('board_id', function ($query) use ($companyId): void {
                $query->select('boards.id')
                    ->from('boards')
                    ->join('workspaces', 'workspaces.id', '=', 'boards.workspace_id')
                    ->where('workspaces.company_id', $companyId);
            })
            ->delete();

        DB::table('card_user')
            ->where('user_id', $user->id)
            ->whereIn('card_id', function ($query) use ($companyId): void {
                $query->select('cards.id')
                    ->from('cards')
                    ->join('lists', 'lists.id', '=', 'cards.list_id')
                    ->join('boards', 'boards.id', '=', 'lists.board_id')
                    ->join('workspaces', 'workspaces.id', '=', 'boards.workspace_id')
                    ->where('workspaces.company_id', $companyId);
            })
            ->delete();
    }
}
