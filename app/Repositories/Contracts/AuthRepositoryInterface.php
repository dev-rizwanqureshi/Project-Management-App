<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use App\Models\User;

interface AuthRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, company: Company}
     */
    public function register(array $data): array;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function attemptLogin(array $credentials): bool;

    public function logout(): void;
}
