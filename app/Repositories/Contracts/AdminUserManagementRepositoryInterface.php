<?php

namespace App\Repositories\Contracts;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;

interface AdminUserManagementRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function indexPayload(Admin $admin, Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function userPage(Admin $admin, User $user, Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function workspacePage(Admin $admin, int $workspaceId, Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function boardPage(Admin $admin, int $boardId, Request $request): array;

    public function setUserRestriction(User $user, bool $restricted): void;
}
