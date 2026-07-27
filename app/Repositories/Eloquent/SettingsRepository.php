<?php

namespace App\Repositories\Eloquent;

use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use App\Repositories\Support\NormalizesNullableStrings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Features;

class SettingsRepository implements SettingsRepositoryInterface
{
    use NormalizesNullableStrings;

    /**
     * @return array<string, mixed>
     */
    public function profilePayload(Request $request): array
    {
        $user = $request->user();

        return [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'canUpdateCompany' => $user instanceof User && $this->canUpdateCompany($user),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): void
    {
        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }

    public function canUpdateCompany(User $user): bool
    {
        if (! $user->company_id) {
            return false;
        }

        return $this->companyRole($user) === 'owner';
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function updateCompany(User $user, array $data): void
    {
        if (! $this->canUpdateCompany($user)) {
            throw new AuthorizationException;
        }

        $company = Company::query()
            ->whereKey($user->company_id)
            ->firstOrFail();

        $company->fill([
            'name' => (string) $data['name'],
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
        ])->save();
    }

    public function deleteProfile(User $user, Request $request): void
    {
        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * @return array<string, mixed>
     */
    public function securityPayload(User $user, TwoFactorAuthenticationRequest $request): array
    {
        $props = [
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'canManagePasskeys' => Features::canManagePasskeys(),
            'passkeys' => Features::canManagePasskeys() ? $this->passkeys($user) : [],
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ];

        if (Features::canManageTwoFactorAuthentication()) {
            $request->ensureStateIsValid();

            $props['twoFactorEnabled'] = $user->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        return $props;
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->update([
            'password' => $password,
        ]);
    }

    /**
     * @return array<int, array{id: int, name: string, authenticator: mixed, created_at_diff: string, last_used_at_diff: string|null}>
     */
    private function passkeys(User $user): array
    {
        return $user
            ->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey): array => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->values()
            ->all();
    }

    private function companyRole(User $user): string
    {
        $role = CompanyUser::query()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->value('role');

        return is_string($role) ? $role : $user->role;
    }
}
