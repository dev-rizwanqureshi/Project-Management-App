<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Repositories\Contracts\AdminUserManagementRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserManagementController extends Controller
{
    public function __construct(
        private readonly AdminUserManagementRepositoryInterface $adminUserManagementRepository,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Users/Index', $this->adminUserManagementRepository->indexPayload($this->admin($request), $request));
    }

    public function show(Request $request, User $user): Response
    {
        return Inertia::render('Admin/Users/Show', $this->adminUserManagementRepository->userPage($this->admin($request), $user, $request));
    }

    public function workspace(Request $request, string $workspace): Response
    {
        return Inertia::render('Admin/Workspaces/Show', $this->adminUserManagementRepository->workspacePage($this->admin($request), (int) $workspace, $request));
    }

    public function board(Request $request, string $board): Response
    {
        return Inertia::render('Admin/Boards/Show', $this->adminUserManagementRepository->boardPage($this->admin($request), (int) $board, $request));
    }

    public function updateUserRestriction(Request $request, User $user): RedirectResponse
    {
        $this->admin($request);

        $validated = $request->validate([
            'restricted' => ['required', 'boolean'],
        ]);

        $this->adminUserManagementRepository->setUserRestriction($user, (bool) $validated['restricted']);

        return back();
    }

    private function admin(Request $request): Admin
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }
}
