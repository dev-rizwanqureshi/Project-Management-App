<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\Board;
use App\Models\Card;
use App\Models\Company;
use App\Models\TaskList;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AdminRolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserDrilldownTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_admin_can_drill_from_users_to_workspaces_boards_and_tickets(): void
    {
        $owner = $this->ownerAdmin();
        [$user, $workspace, $board] = $this->projectData();

        $this->actingAs($owner, 'admin')
            ->get(route('admin.users.index', ['search' => 'Builder']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('users.data.0.name', 'Riraa Builder')
                ->where('users.data.0.workspaces_count', 1)
                ->where('users.data.0.boards_count', 1)
                ->where('users.data.0.tickets_count', 1)
                ->where('can.restrict_users', true)
            );

        $this->actingAs($owner, 'admin')
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Show')
                ->where('user.name', 'Riraa Builder')
                ->where('workspaces.data.0.name', 'Product Workspace')
                ->where('workspaces.data.0.boards_count', 1)
                ->where('workspaces.data.0.tickets_count', 1)
                ->where('workspaces.data.0.users_count', 2)
                ->where('can.view_workspaces', true)
                ->where('can.restrict_workspaces', true)
            );

        $this->actingAs($owner, 'admin')
            ->get(route('admin.workspaces.show', $workspace))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Workspaces/Show')
                ->where('workspace.name', 'Product Workspace')
                ->where('boards.data.0.name', 'Launch Board')
                ->where('boards.data.0.tickets_count', 1)
                ->where('boards.data.0.users_count', 2)
                ->where('can.view_boards', true)
                ->where('can.restrict_boards', true)
            );

        $this->actingAs($owner, 'admin')
            ->get(route('admin.boards.show', $board))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Boards/Show')
                ->where('board.name', 'Launch Board')
                ->where('cards.data.0.title', 'Prepare launch checklist')
                ->where('cards.data.0.creator.name', 'Riraa Builder')
                ->where('cards.data.0.assignees_count', 2)
                ->where('can.view_tickets', true)
                ->where('can.restrict_tickets', true)
            );
    }

    public function test_owner_admin_can_open_top_level_platform_listings(): void
    {
        $owner = $this->ownerAdmin();
        $this->projectData();

        $this->actingAs($owner, 'admin')
            ->get(route('admin.companies.index', ['search' => 'Riraa Studio']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Companies/Index')
                ->where('companies.data.0.name', 'Riraa Studio')
                ->where('companies.data.0.users_count', 2)
                ->where('companies.data.0.workspaces_count', 1)
                ->where('companies.data.0.boards_count', 1)
                ->where('companies.data.0.tickets_count', 1)
            );

        $this->actingAs($owner, 'admin')
            ->get(route('admin.workspaces.index', ['search' => 'Product']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Workspaces/Index')
                ->where('workspaces.data.0.name', 'Product Workspace')
                ->where('workspaces.data.0.boards_count', 1)
                ->where('workspaces.data.0.tickets_count', 1)
                ->where('workspaces.data.0.users_count', 2)
                ->where('can.restrict_workspaces', true)
            );

        $this->actingAs($owner, 'admin')
            ->get(route('admin.boards.index', ['search' => 'Launch']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Boards/Index')
                ->where('boards.data.0.name', 'Launch Board')
                ->where('boards.data.0.workspace.name', 'Product Workspace')
                ->where('boards.data.0.tickets_count', 1)
                ->where('boards.data.0.users_count', 2)
                ->where('can.restrict_boards', true)
            );

        $this->actingAs($owner, 'admin')
            ->get(route('admin.cards.index', ['search' => 'Prepare']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Tickets/Index')
                ->where('cards.data.0.title', 'Prepare launch checklist')
                ->where('cards.data.0.board.name', 'Launch Board')
                ->where('cards.data.0.workspace.name', 'Product Workspace')
                ->where('cards.data.0.assignees_count', 2)
                ->where('can.restrict_tickets', true)
            );
    }

    public function test_platform_listings_can_be_filtered_from_parent_links(): void
    {
        $owner = $this->ownerAdmin();
        [$user, $workspace, $board] = $this->projectData();
        $this->otherProjectData();

        $this->actingAs($owner, 'admin')
            ->get(route('admin.users.index', ['company_id' => $user->company_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('filters.company_id', $user->company_id)
                ->where('users.total', 2)
            );

        $this->actingAs($owner, 'admin')
            ->get(route('admin.workspaces.index', ['company_id' => $user->company_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Workspaces/Index')
                ->where('filters.company_id', $user->company_id)
                ->where('workspaces.total', 1)
                ->where('workspaces.data.0.id', $workspace->id)
            );

        $this->actingAs($owner, 'admin')
            ->get(route('admin.workspaces.index', ['user_id' => $user->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Workspaces/Index')
                ->where('filters.user_id', $user->id)
                ->where('workspaces.total', 1)
                ->where('workspaces.data.0.id', $workspace->id)
            );

        $this->actingAs($owner, 'admin')
            ->get(route('admin.boards.index', ['workspace_id' => $workspace->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Boards/Index')
                ->where('filters.workspace_id', $workspace->id)
                ->where('boards.total', 1)
                ->where('boards.data.0.id', $board->id)
            );

        $this->actingAs($owner, 'admin')
            ->get(route('admin.cards.index', ['board_id' => $board->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Tickets/Index')
                ->where('filters.board_id', $board->id)
                ->where('cards.total', 1)
                ->where('cards.data.0.title', 'Prepare launch checklist')
            );
    }

    public function test_owner_admin_can_restrict_and_restore_listing_rows(): void
    {
        $owner = $this->ownerAdmin();
        [$user, $workspace, $board] = $this->projectData();

        /** @var Company $company */
        $company = Company::query()->findOrFail($user->company_id);
        /** @var Card $card */
        $card = Card::query()->firstOrFail();

        $this->assertRestrictionCanBeToggled(
            $owner,
            route('admin.companies.restriction.update', $company),
            route('admin.companies.index', ['search' => 'Riraa Studio']),
            route('admin.companies.index', ['search' => 'Riraa Studio', 'restriction' => 'restricted']),
            'companies',
        );

        $this->assertRestrictionCanBeToggled(
            $owner,
            route('admin.users.restriction.update', $user),
            route('admin.users.index', ['search' => 'Builder']),
            route('admin.users.index', ['search' => 'Builder', 'restriction' => 'restricted']),
            'users',
        );

        $this->assertRestrictionCanBeToggled(
            $owner,
            route('admin.workspaces.restriction.update', $workspace),
            route('admin.workspaces.index', ['search' => 'Product']),
            route('admin.workspaces.index', ['search' => 'Product', 'restriction' => 'restricted']),
            'workspaces',
        );

        $this->assertRestrictionCanBeToggled(
            $owner,
            route('admin.boards.restriction.update', $board),
            route('admin.boards.index', ['search' => 'Launch']),
            route('admin.boards.index', ['search' => 'Launch', 'restriction' => 'restricted']),
            'boards',
        );

        $this->assertRestrictionCanBeToggled(
            $owner,
            route('admin.cards.restriction.update', $card),
            route('admin.cards.index', ['search' => 'Prepare']),
            route('admin.cards.index', ['search' => 'Prepare', 'restriction' => 'restricted']),
            'cards',
        );
    }

    public function test_admin_without_restrict_permission_cannot_restrict_listing_rows(): void
    {
        [, $workspace] = $this->projectData();
        $admin = $this->adminWithPermissions([
            'admin.workspaces.view',
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.workspaces.restriction.update', $workspace), [
                'restricted' => true,
            ])
            ->assertForbidden();
    }

    public function test_nested_listings_are_hidden_without_the_next_listing_permission(): void
    {
        [$user, $workspace, $board] = $this->projectData();

        $workspaceViewer = $this->adminWithPermissions([
            'admin.users.view',
            'admin.workspaces.view',
        ]);

        $this->actingAs($workspaceViewer, 'admin')
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Show')
                ->where('can.view_workspaces', true)
                ->where('workspaces.data.0.name', 'Product Workspace')
            );

        $this->actingAs($workspaceViewer, 'admin')
            ->get(route('admin.workspaces.show', $workspace))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Workspaces/Show')
                ->where('can.view_boards', false)
                ->where('boards.total', 0)
            );

        $boardViewer = $this->adminWithPermissions([
            'admin.users.view',
            'admin.workspaces.view',
            'admin.boards.view',
        ]);

        $this->actingAs($boardViewer, 'admin')
            ->get(route('admin.workspaces.show', $workspace))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Workspaces/Show')
                ->where('can.view_boards', true)
                ->where('boards.data.0.name', 'Launch Board')
            );

        $this->actingAs($boardViewer, 'admin')
            ->get(route('admin.boards.show', $board))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Boards/Show')
                ->where('can.view_tickets', false)
                ->where('cards.total', 0)
            );
    }

    private function ownerAdmin(): Admin
    {
        $owner = Admin::factory()->create(['role' => 'owner']);
        $roles = app(AdminRolePermissionDefaults::class)->ensureRoles($owner);

        /** @var AdminRole|null $ownerRole */
        $ownerRole = $roles->get('owner');

        $owner->forceFill(['admin_role_id' => $ownerRole?->id])->save();

        return $owner;
    }

    private function assertRestrictionCanBeToggled(
        Admin $admin,
        string $restrictionRoute,
        string $activeListingRoute,
        string $restrictedListingRoute,
        string $payloadKey,
    ): void {
        $this->actingAs($admin, 'admin')
            ->put($restrictionRoute, ['restricted' => true])
            ->assertRedirect();

        $this->actingAs($admin, 'admin')
            ->get($activeListingRoute)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where("{$payloadKey}.total", 0)
            );

        $this->actingAs($admin, 'admin')
            ->get($restrictedListingRoute)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.restriction', 'restricted')
                ->where("{$payloadKey}.total", 1)
                ->where("{$payloadKey}.data.0.is_restricted", true)
            );

        $this->actingAs($admin, 'admin')
            ->put($restrictionRoute, ['restricted' => false])
            ->assertRedirect();

        $this->actingAs($admin, 'admin')
            ->get($activeListingRoute)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where("{$payloadKey}.total", 1)
                ->where("{$payloadKey}.data.0.is_restricted", false)
            );
    }

    /**
     * @param  list<string>  $permissionSlugs
     */
    private function adminWithPermissions(array $permissionSlugs): Admin
    {
        app(AdminRolePermissionDefaults::class)->ensurePermissions();

        $roleNumber = AdminRole::query()->count() + 1;
        $role = AdminRole::query()->create([
            'name' => "Test Role {$roleNumber}",
            'slug' => "test-role-{$roleNumber}",
            'is_system' => false,
        ]);

        $role->permissions()->sync(
            AdminPermission::query()
                ->whereIn('slug', $permissionSlugs)
                ->pluck('id')
                ->all(),
        );

        $admin = Admin::factory()->create(['role' => 'support_staff']);
        $admin->forceFill(['admin_role_id' => $role->id])->save();

        return $admin;
    }

    /**
     * @return array{0: User, 1: Workspace, 2: Board}
     */
    private function projectData(): array
    {
        $company = Company::factory()->create(['name' => 'Riraa Studio', 'slug' => 'riraa-studio']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Riraa Builder',
            'email' => 'builder@riraa.test',
            'role' => 'owner',
        ]);
        $teammate = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Riraa Teammate',
            'email' => 'teammate@riraa.test',
            'role' => 'member',
        ]);

        $workspace = Workspace::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Product Workspace',
            'slug' => 'product-workspace',
            'description' => 'Core product planning.',
            'color' => '#8b5cf6',
            'created_by' => $user->id,
        ]);
        $workspace->users()->attach([
            $user->id => ['role' => 'owner'],
            $teammate->id => ['role' => 'member'],
        ]);

        $board = Board::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Launch Board',
            'description' => 'Launch tasks.',
            'background' => '#ede9fe',
            'is_private' => false,
            'is_archived' => false,
            'created_by' => $user->id,
        ]);
        $board->users()->attach([
            $user->id => ['role' => 'owner'],
            $teammate->id => ['role' => 'member'],
        ]);

        $list = TaskList::query()->create([
            'board_id' => $board->id,
            'name' => 'To do',
            'position' => 1,
            'is_archived' => false,
        ]);

        $card = Card::query()->create([
            'list_id' => $list->id,
            'title' => 'Prepare launch checklist',
            'description' => 'Confirm the admin drilldown numbers.',
            'position' => 1,
            'is_completed' => false,
            'is_archived' => false,
            'created_by' => $user->id,
        ]);
        $card->assignees()->attach([$user->id, $teammate->id]);

        return [$user, $workspace, $board];
    }

    private function otherProjectData(): void
    {
        $company = Company::factory()->create(['name' => 'Other Studio', 'slug' => 'other-studio']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Other Builder',
            'email' => 'other-builder@riraa.test',
            'role' => 'owner',
        ]);

        $workspace = Workspace::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Other Workspace',
            'slug' => 'other-workspace',
            'description' => 'A separate project.',
            'color' => '#7c3aed',
            'created_by' => $user->id,
        ]);

        $board = Board::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Other Board',
            'description' => 'Separate work.',
            'background' => '#f5f3ff',
            'is_private' => false,
            'is_archived' => false,
            'created_by' => $user->id,
        ]);

        $list = TaskList::query()->create([
            'board_id' => $board->id,
            'name' => 'To do',
            'position' => 1,
            'is_archived' => false,
        ]);

        Card::query()->create([
            'list_id' => $list->id,
            'title' => 'Other ticket',
            'description' => 'This should be filtered away.',
            'position' => 1,
            'is_completed' => false,
            'is_archived' => false,
            'created_by' => $user->id,
        ]);
    }
}
