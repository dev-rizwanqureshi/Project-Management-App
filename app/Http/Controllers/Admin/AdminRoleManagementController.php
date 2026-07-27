<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Repositories\Contracts\AdminRoleManagementRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRoleManagementController extends Controller
{
    public function __construct(
        private readonly AdminRoleManagementRepositoryInterface $adminRoleManagementRepository,
    ) {}

    public function index(Request $request): Response
    {
        $this->adminUser($request);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $this->adminRoleManagementRepository->roles(),
        ]);
    }

    public function permissions(Request $request, AdminRole $adminRole): Response
    {
        $this->adminUser($request);

        return Inertia::render('Admin/Roles/Permissions', $this->adminRoleManagementRepository->permissionsPage($adminRole));
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = $this->adminUser($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:admin_permissions,slug'],
        ]);

        $name = (string) $validated['name'];
        $permissionSlugs = $validated['permissions'] ?? ['admin.dashboard.view'];

        $this->adminRoleManagementRepository->createRole($admin, $name, $permissionSlugs);

        return back();
    }

    public function updatePermissions(Request $request, AdminRole $adminRole): RedirectResponse
    {
        $this->adminUser($request);

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:admin_permissions,slug'],
        ]);

        $this->adminRoleManagementRepository->updatePermissions($adminRole, $validated['permissions'] ?? []);

        return back();
    }

    private function adminUser(Request $request): Admin
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }
}
