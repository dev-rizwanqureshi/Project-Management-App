<?php

namespace App\Repositories\Contracts;

use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\User;
use Illuminate\Http\Request;

interface SettingsRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function profilePayload(Request $request): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): void;

    public function canUpdateCompany(User $user): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCompany(User $user, array $data): void;

    public function deleteProfile(User $user, Request $request): void;

    /**
     * @return array<string, mixed>
     */
    public function securityPayload(User $user, TwoFactorAuthenticationRequest $request): array;

    public function updatePassword(User $user, string $password): void;
}
