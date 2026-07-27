<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Repositories\Contracts\AdminStaffRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminStaffManagementController extends Controller
{
    public function __construct(
        private readonly AdminStaffRepositoryInterface $adminStaffRepository,
    ) {}

    public function index(Request $request): Response
    {
        $admin = $this->admin($request);

        return Inertia::render('Admin/Admins/Index', $this->adminStaffRepository->indexPayload($admin, $request));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->admin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('admins', 'email')],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'admin_role_id' => ['required', 'integer', 'exists:admin_roles,id'],
        ]);

        $this->adminStaffRepository->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?? null,
            'admin_role_id' => (int) $validated['admin_role_id'],
        ]);

        return back();
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $this->admin($request);

        $validated = $request->validate([
            'admin_role_id' => ['required', 'integer', 'exists:admin_roles,id'],
        ]);

        $this->adminStaffRepository->assignRole($admin, (int) $validated['admin_role_id']);

        return back();
    }

    private function admin(Request $request): Admin
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }
}
