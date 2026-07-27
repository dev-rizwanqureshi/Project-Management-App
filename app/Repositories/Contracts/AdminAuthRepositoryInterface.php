<?php

namespace App\Repositories\Contracts;

use Illuminate\Http\Request;

interface AdminAuthRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function attemptLogin(array $credentials, Request $request): bool;

    public function logout(Request $request): void;
}
