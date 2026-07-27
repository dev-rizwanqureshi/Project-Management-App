<?php

namespace App\Repositories\Contracts;

use App\Models\Admin;
use App\Models\Board;
use App\Models\Card;
use App\Models\Company;
use App\Models\Workspace;
use Illuminate\Http\Request;

interface AdminPlatformListingRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function companies(Admin $admin, Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function workspaces(Admin $admin, Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function boards(Admin $admin, Request $request): array;

    /**
     * @return array<string, mixed>
     */
    public function cards(Admin $admin, Request $request): array;

    public function setCompanyRestriction(Company $company, bool $restricted): void;

    public function setWorkspaceRestriction(Workspace $workspace, bool $restricted): void;

    public function setBoardRestriction(Board $board, bool $restricted): void;

    public function setCardRestriction(Card $card, bool $restricted): void;
}
