<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Board;
use App\Models\Card;
use App\Models\Company;
use App\Models\Workspace;
use App\Repositories\Contracts\AdminPlatformListingRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPlatformListingController extends Controller
{
    public function __construct(
        private readonly AdminPlatformListingRepositoryInterface $adminPlatformListingRepository,
    ) {}

    public function companies(Request $request): Response
    {
        return Inertia::render('Admin/Companies/Index', $this->adminPlatformListingRepository->companies($this->admin($request), $request));
    }

    public function workspaces(Request $request): Response
    {
        return Inertia::render('Admin/Workspaces/Index', $this->adminPlatformListingRepository->workspaces($this->admin($request), $request));
    }

    public function boards(Request $request): Response
    {
        return Inertia::render('Admin/Boards/Index', $this->adminPlatformListingRepository->boards($this->admin($request), $request));
    }

    public function cards(Request $request): Response
    {
        return Inertia::render('Admin/Tickets/Index', $this->adminPlatformListingRepository->cards($this->admin($request), $request));
    }

    public function updateCompanyRestriction(Request $request, Company $company): RedirectResponse
    {
        $this->admin($request);
        $this->adminPlatformListingRepository->setCompanyRestriction($company, $this->restricted($request));

        return back();
    }

    public function updateWorkspaceRestriction(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->admin($request);
        $this->adminPlatformListingRepository->setWorkspaceRestriction($workspace, $this->restricted($request));

        return back();
    }

    public function updateBoardRestriction(Request $request, Board $board): RedirectResponse
    {
        $this->admin($request);
        $this->adminPlatformListingRepository->setBoardRestriction($board, $this->restricted($request));

        return back();
    }

    public function updateCardRestriction(Request $request, Card $card): RedirectResponse
    {
        $this->admin($request);
        $this->adminPlatformListingRepository->setCardRestriction($card, $this->restricted($request));

        return back();
    }

    private function admin(Request $request): Admin
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }

    private function restricted(Request $request): bool
    {
        $validated = $request->validate([
            'restricted' => ['required', 'boolean'],
        ]);

        return (bool) $validated['restricted'];
    }
}
