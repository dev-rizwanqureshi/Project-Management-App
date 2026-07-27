<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Card;
use App\Models\Company;
use App\Models\TaskList;
use App\Models\User;
use App\Models\Workspace;
use App\Services\RolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_company_scoped_sidebar_listings(): void
    {
        [$owner] = $this->projectData();

        $this->actingAs($owner)
            ->get(route('companies.index', ['search' => 'Riraa Studio']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Index')
                ->where('companies.data.0.name', 'Riraa Studio')
                ->where('companies.data.0.users_count', 2)
                ->where('companies.data.0.workspaces_count', 1)
                ->where('companies.data.0.boards_count', 1)
                ->where('companies.data.0.tickets_count', 1)
            );

        $this->actingAs($owner)
            ->get(route('users.index', ['search' => 'Builder']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->where('users.data.0.name', 'Riraa Builder')
                ->where('users.data.0.workspaces_count', 1)
                ->where('users.data.0.boards_count', 1)
                ->where('users.data.0.tickets_count', 1)
            );

        $this->actingAs($owner)
            ->get(route('workspaces.index', ['search' => 'Product']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Workspaces/Index')
                ->where('workspaces.data.0.name', 'Product Workspace')
                ->where('workspaces.data.0.boards_count', 1)
                ->where('workspaces.data.0.tickets_count', 1)
                ->where('workspaces.data.0.users_count', 2)
            );

        $this->actingAs($owner)
            ->get(route('boards.index', ['search' => 'Launch']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Boards/Index')
                ->where('boards.data.0.name', 'Launch Board')
                ->where('boards.data.0.workspace.name', 'Product Workspace')
                ->where('boards.data.0.tickets_count', 1)
                ->where('boards.data.0.users_count', 2)
            );

        $this->actingAs($owner)
            ->get(route('cards.index', ['search' => 'Prepare']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tickets/Index')
                ->where('cards.data.0.title', 'Prepare launch checklist')
                ->where('cards.data.0.board.name', 'Launch Board')
                ->where('cards.data.0.workspace.name', 'Product Workspace')
                ->where('cards.data.0.assignees_count', 2)
            );
    }

    /**
     * @return array{0: User}
     */
    private function projectData(): array
    {
        $company = Company::factory()->create(['name' => 'Riraa Studio', 'slug' => 'riraa-studio']);
        $owner = User::factory()->create([
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

        $roles = app(RolePermissionDefaults::class)->ensureForCompany($company, $owner);
        $owner->forceFill(['role_id' => $roles->get('owner')?->id])->save();
        $teammate->forceFill(['role_id' => $roles->get('member')?->id])->save();

        $workspace = Workspace::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Product Workspace',
            'slug' => 'product-workspace',
            'description' => 'Core product planning.',
            'color' => '#8b5cf6',
            'created_by' => $owner->id,
        ]);
        $workspace->users()->attach([
            $owner->id => ['role' => 'owner'],
            $teammate->id => ['role' => 'member'],
        ]);

        $board = Board::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Launch Board',
            'description' => 'Launch tasks.',
            'background' => '#ede9fe',
            'is_private' => false,
            'is_archived' => false,
            'created_by' => $owner->id,
        ]);
        $board->users()->attach([
            $owner->id => ['role' => 'owner'],
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
            'description' => 'Confirm the company listing numbers.',
            'position' => 1,
            'is_completed' => false,
            'is_archived' => false,
            'created_by' => $owner->id,
        ]);
        $card->assignees()->attach([$owner->id, $teammate->id]);

        return [$owner];
    }
}
