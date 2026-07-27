<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\AdminRole;
use App\Repositories\Contracts\AdminStaffRepositoryInterface;
use App\Repositories\Support\BuildsListingPayloads;
use App\Services\AdminRolePermissionDefaults;
use Illuminate\Http\Request;

class AdminStaffRepository implements AdminStaffRepositoryInterface
{
    use BuildsListingPayloads;

    public function __construct(
        private readonly AdminRolePermissionDefaults $adminRolePermissionDefaults,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexPayload(Admin $admin, Request $request): array
    {
        $this->adminRolePermissionDefaults->ensureRoles($admin);

        $filters = $this->filters($request);
        $sort = $this->sort($request, [
            'name',
            'email',
            'role_name',
            'created_at',
            'updated_at',
        ], 'name');

        $query = Admin::query()
            ->select('admins.*')
            ->leftJoin('admin_roles', 'admin_roles.id', '=', 'admins.admin_role_id')
            ->with('adminRole:id,name,slug,is_system');

        $this->searchColumns($query, $filters['search'], [
            'admins.name',
            'admins.email',
            'admins.role',
            'admin_roles.name',
        ]);
        $this->orderByColumns($query, $sort, [
            'name' => 'admins.name',
            'email' => 'admins.email',
            'role_name' => 'admin_roles.name',
            'created_at' => 'admins.created_at',
            'updated_at' => 'admins.updated_at',
        ]);

        $admins = $query
            ->orderBy('admins.id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Admin $staff): array => $this->adminRow($staff));

        return [
            'admins' => $this->paginatorPayload($admins),
            'roles' => $this->roles(),
            'filters' => $filters,
            'sort' => $sort,
            'can' => [
                'manage_admins' => $admin->hasPermission('admin.admins.manage'),
            ],
        ];
    }

    /**
     * @param  array{name: string, email: string, password?: string|null, admin_role_id: int}  $data
     */
    public function create(array $data): void
    {
        $role = AdminRole::query()->findOrFail($data['admin_role_id']);

        Admin::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => filled($data['password'] ?? null) ? $data['password'] : 'password',
            'role' => $role->slug,
            'admin_role_id' => $role->id,
        ]);
    }

    public function assignRole(Admin $admin, int $roleId): void
    {
        $role = AdminRole::query()->findOrFail($roleId);

        $this->ensureAnOwnerRemains($admin, $role);

        $admin->forceFill([
            'role' => $role->slug,
            'admin_role_id' => $role->id,
        ])->save();
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string, is_system: bool}>
     */
    private function roles(): array
    {
        return AdminRole::query()
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (AdminRole $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'is_system' => $role->is_system,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, name: string, email: string, role: string, role_name: string, admin_role_id: int|null, is_owner: bool, created_at: string|null, updated_at: string|null}
     */
    private function adminRow(Admin $admin): array
    {
        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
            'role_name' => $admin->admin_role_id ? $admin->adminRole->name : $this->roleName($admin->role),
            'admin_role_id' => $admin->admin_role_id,
            'is_owner' => $admin->role === 'owner',
            'created_at' => $this->date($admin->created_at),
            'updated_at' => $this->date($admin->updated_at),
        ];
    }

    private function ensureAnOwnerRemains(Admin $admin, AdminRole $role): void
    {
        if ($admin->role !== 'owner' || $role->slug === 'owner') {
            return;
        }

        abort_unless(
            Admin::query()->where('role', 'owner')->whereKeyNot($admin->id)->exists(),
            422,
            'At least one owner admin is required.',
        );
    }
}
