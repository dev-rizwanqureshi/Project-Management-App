<?php

namespace App\Services\Contracts;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

interface AuthServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, company: Company}
     */
    public function registerCompany(array $data): array;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function login(array $credentials, Request $request): bool;

    public function logout(Request $request): void;

    public function getProfile(): ?User;
}
