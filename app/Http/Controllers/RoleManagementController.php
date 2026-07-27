<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\RoleManagementRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleManagementController extends Controller
{
    public function __construct(
        private readonly RoleManagementRepositoryInterface $roleManagementRepository,
    ) {}

    public function index(Request $request): Response
    {
        $user = $this->ownerUser($request);

        return Inertia::render('Roles/Index', [
            'roles' => $this->roleManagementRepository->rolesForUser($user),
        ]);
    }

    public function permissions(Request $request, Role $role): Response
    {
        $user = $this->ownerUser($request);

        return Inertia::render('Roles/Permissions', $this->roleManagementRepository->permissionsPage($user, $role));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->ownerUser($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,slug'],
        ]);

        $name = (string) $validated['name'];
        $permissionSlugs = $validated['permissions'] ?? ['dashboard.view'];

        $this->roleManagementRepository->createRole($user, $name, $permissionSlugs);

        return back();
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $user = $this->ownerUser($request);

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,slug'],
        ]);

        $this->roleManagementRepository->updatePermissions($user, $role, $validated['permissions'] ?? []);

        return back();
    }

    private function ownerUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
