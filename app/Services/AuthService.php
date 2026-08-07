<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, company: Company}
     */
    public function registerCompany(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $registered = $this->authRepository->register($data);

            Auth::login($registered['user']);

            return $registered;
        });
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function login(array $credentials, Request $request): bool
    {
        if (! $this->authRepository->attemptLogin($credentials)) {
            return false;
        }

        $request->session()->regenerate();

        return true;
    }

    public function logout(Request $request): void
    {
        $this->authRepository->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
