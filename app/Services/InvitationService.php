<?php

namespace App\Services;

use App\Mail\InvitationMail;
use App\Models\Board;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\CompanyMembershipRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function __construct(
        private readonly CompanyMembershipRepositoryInterface $membershipRepository,
        private readonly RolePermissionDefaults $roleDefaults,
    ) {}

    /**
     * @return array{invitation: Invitation, token: string}
     */
    public function create(
        User $inviter,
        Company $company,
        string $email,
        string $scope,
        ?Workspace $workspace,
        ?Board $board,
        string $role,
    ): array {
        $email = mb_strtolower(trim($email));
        $token = Str::random(64);

        $invitation = DB::transaction(function () use ($inviter, $company, $email, $scope, $workspace, $board, $role, $token): Invitation {
            Invitation::query()
                ->pending()
                ->forEmail($email)
                ->where('company_id', $company->id)
                ->when($scope === 'company', fn ($query) => $query->whereNull('workspace_id')->whereNull('board_id'))
                ->when($scope === 'workspace', fn ($query) => $query->where('workspace_id', $workspace?->id)->whereNull('board_id'))
                ->when($scope === 'board', fn ($query) => $query->where('board_id', $board?->id))
                ->update(['expires_at' => now()]);

            return Invitation::query()->create([
                'company_id' => $company->id,
                'workspace_id' => $workspace?->id,
                'board_id' => $board?->id,
                'invited_by' => $inviter->id,
                'email' => $email,
                'role' => $role,
                'token' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
            ]);
        });

        Mail::to($email)->send(new InvitationMail($invitation, $token));

        return ['invitation' => $invitation, 'token' => $token];
    }

    public function findPending(string $token): Invitation
    {
        $invitation = Invitation::query()
            ->with([
                'company:id,name',
                'workspace:id,name',
                'board:id,name',
            ])
            ->where('token', hash('sha256', $token))
            ->firstOrFail();

        abort_unless($invitation->isPending(), 410, 'This invitation has expired or was already accepted.');

        return $invitation;
    }

    public function accept(Invitation $invitation, User $user): void
    {
        if (! hash_equals(mb_strtolower($invitation->email), mb_strtolower($user->email))) {
            throw ValidationException::withMessages([
                'invitation' => 'This invitation was sent to a different email address.',
            ]);
        }

        DB::transaction(function () use ($invitation, $user): void {
            $lockedInvitation = Invitation::query()->lockForUpdate()->findOrFail($invitation->id);

            if (! $lockedInvitation->isPending()) {
                throw ValidationException::withMessages([
                    'invitation' => 'This invitation has expired or was already accepted.',
                ]);
            }

            /** @var Company $company */
            $company = $lockedInvitation->company()->firstOrFail();

            $activeMembership = CompanyUser::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
            $activeCompanyId = $activeMembership === null
                ? $user->company_id
                : $activeMembership->company_id;
            $activeMembershipCompanyId = $activeMembership === null
                ? null
                : $activeMembership->company_id;
            $activeMembershipRole = $activeMembership === null
                ? null
                : $activeMembership->role;

            if ($activeCompanyId !== null && $activeCompanyId !== $company->id) {
                throw ValidationException::withMessages([
                    'invitation' => 'Leave your current company before accepting this invitation.',
                ]);
            }

            $companyRole = $lockedInvitation->scopeLabel() === 'company'
                ? $lockedInvitation->role
                : ($lockedInvitation->role === 'guest' ? 'guest' : 'member');

            if ($activeMembershipCompanyId === $company->id) {
                $companyRole = $lockedInvitation->scopeLabel() === 'company'
                    ? $this->strongerRole((string) $activeMembershipRole, $companyRole)
                    : (string) $activeMembershipRole;
            } elseif ($user->company_id === $company->id) {
                // Repair legacy accounts that have users.company_id but no company_user row.
                $companyRole = $this->strongerRole($user->role, $companyRole);
            }

            $needsCompanyMembership = $user->company_id !== $company->id
                || $activeMembershipCompanyId !== $company->id
                || ($lockedInvitation->scopeLabel() === 'company'
                    && $activeMembershipRole !== $companyRole);

            if ($needsCompanyMembership) {
                $roles = $this->roleDefaults->ensureForCompany($company, $user);
                $this->membershipRepository->joinCompany(
                    $user,
                    $company,
                    $companyRole,
                    $roles->get($companyRole),
                    $lockedInvitation->scopeLabel() === 'company',
                );
            }

            if ($lockedInvitation->scopeLabel() === 'company') {
                CompanyUser::query()
                    ->where('company_id', $company->id)
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->update(['is_company_wide' => true]);
            }

            if ($lockedInvitation->workspace_id !== null) {
                /** @var Workspace $workspace */
                $workspace = $lockedInvitation->workspace()->firstOrFail();
                $workspace->users()->syncWithoutDetaching([
                    $user->id => ['role' => $lockedInvitation->role],
                ]);
            }

            if ($lockedInvitation->board_id !== null) {
                /** @var Board $board */
                $board = $lockedInvitation->board()->firstOrFail();
                $board->users()->syncWithoutDetaching([
                    $user->id => ['role' => $lockedInvitation->role],
                ]);
            }

            $lockedInvitation->forceFill(['accepted_at' => now()])->save();
        });
    }

    private function strongerRole(string $currentRole, string $invitedRole): string
    {
        $rank = [
            'guest' => 1,
            'member' => 2,
            'admin' => 3,
            'owner' => 4,
        ];

        if (! array_key_exists($currentRole, $rank)) {
            return $invitedRole;
        }

        $currentRank = $rank[$currentRole];
        $invitedRank = array_key_exists($invitedRole, $rank) ? $rank[$invitedRole] : 0;

        return $currentRank >= $invitedRank
            ? $currentRole
            : $invitedRole;
    }

    public function registerAndAccept(Invitation $invitation, string $name, string $password): User
    {
        return DB::transaction(function () use ($invitation, $name, $password): User {
            $user = User::query()->create([
                'name' => trim($name),
                'email' => $invitation->email,
                'password' => Hash::make($password),
                'role' => 'member',
            ]);

            $this->accept($invitation, $user);

            return $user->refresh()->load('company', 'roleDefinition.permissions', 'activeCompanyMembership');
        });
    }
}
