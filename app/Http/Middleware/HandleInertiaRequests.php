<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $admin = $request->user('admin');

        if ($user instanceof User) {
            $user->loadMissing('company', 'roleDefinition.permissions', 'activeCompanyMembership');
        }

        if ($admin instanceof Admin) {
            $admin->loadMissing('adminRole.permissions');
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'permissions' => $user instanceof User ? $user->permissionSlugs() : [],
            ],
            'projectContext' => $user instanceof User ? $this->projectContext($user) : null,
            'adminAuth' => [
                'admin' => $admin,
                'permissions' => $admin instanceof Admin ? $admin->permissionSlugs() : [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function projectContext(User $user): ?array
    {
        $company = $user->company;

        if (! $company) {
            return null;
        }

        $hasCompanyWideAccess = $user->hasCompanyWideAccess();

        $workspaceModels = Workspace::query()
            ->where('company_id', $company->id)
            ->where('is_restricted', false)
            ->when(! $hasCompanyWideAccess, function ($query) use ($user): void {
                $query->where(function ($accessQuery) use ($user): void {
                    $accessQuery
                        ->whereHas('users', fn ($usersQuery) => $usersQuery->whereKey($user->id))
                        ->orWhereHas(
                            'boards.users',
                            fn ($usersQuery) => $usersQuery->whereKey($user->id),
                        );
                });
            })
            ->withCount([
                'boards' => fn ($query) => $query
                    ->where('is_restricted', false)
                    ->where('is_archived', false),
            ])
            ->with([
                'boards' => fn ($query) => $query
                    ->select('id', 'workspace_id', 'name', 'description', 'background')
                    ->where('is_restricted', false)
                    ->where('is_archived', false)
                    ->when(! $hasCompanyWideAccess, function ($query) use ($user): void {
                        $query->where(function ($accessQuery) use ($user): void {
                            $accessQuery
                                ->whereHas('users', fn ($usersQuery) => $usersQuery->whereKey($user->id))
                                ->orWhereHas(
                                    'workspace.users',
                                    fn ($usersQuery) => $usersQuery->whereKey($user->id),
                                );
                        });
                    })
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'slug', 'description', 'color']);

        $workspaces = [];
        foreach ($workspaceModels as $workspace) {
            $boards = [];
            foreach ($workspace->boards as $board) {
                $boards[] = [
                    'id' => $board->id,
                    'name' => $board->name,
                    'description' => $board->description,
                    'background' => $board->background,
                ];
            }

            $workspaces[] = [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'description' => $workspace->description,
                'color' => $workspace->color,
                'boards_count' => (int) $workspace->getAttribute('boards_count'),
                'boards' => $boards,
            ];
        }

        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'logo' => $company->logo,
            ],
            'workspaces' => $workspaces,
        ];
    }
}
