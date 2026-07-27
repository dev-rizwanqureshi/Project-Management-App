<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Http\Request;

interface CompanyListingRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function companies(User $user, Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function users(User $user, Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function workspaces(User $user, Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function boards(User $user, Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function cards(User $user, Request $request): array;
}
