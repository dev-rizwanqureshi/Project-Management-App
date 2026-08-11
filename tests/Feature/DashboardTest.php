<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Card;
use App\Models\Company;
use App\Models\User;
use App\Models\Workspace;
use App\Services\RolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('canViewAnalytics', true)
                ->where('overview.open_tasks', 0)
                ->where('overview.due_this_week', 0)
                ->has('stats', 6)
            );
    }

    public function test_admin_dashboard_hides_owner_analytics_by_default()
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->create(['role' => 'owner']);
        $admin = User::factory()->for($company)->create(['role' => 'admin']);
        $roles = app(RolePermissionDefaults::class)->ensureForCompany($company, $owner);

        $admin->forceFill(['role_id' => $roles->get('admin')?->id])->save();
        $workspace = Workspace::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Admin workspace',
            'slug' => 'admin-workspace',
            'created_by' => $owner->id,
        ]);
        $board = Board::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Admin board',
            'created_by' => $owner->id,
        ]);
        $list = $board->lists()->create(['name' => 'To do', 'position' => 1]);
        Card::query()->create([
            'list_id' => $list->id,
            'title' => 'Visible admin task',
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('canViewAnalytics', false)
                ->where('overview.open_tasks', 1)
                ->where('overview.total_tasks', 1)
                ->where('overview.workspaces', 1)
                ->where('overview.boards', 1)
                ->where('overview.people', 2)
                ->where('ticketChart.0.value', 1)
                ->has('ticketChart', 3)
                ->has('stats', 0)
            );
    }

    public function test_dashboard_operational_stats_are_live_and_use_real_due_dates(): void
    {
        Carbon::setTestNow('2026-08-05 12:00:00');

        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->create(['role' => 'owner']);
        User::factory()->for($company)->create(['role' => 'member']);
        $workspace = Workspace::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Delivery',
            'slug' => 'delivery',
            'created_by' => $owner->id,
        ]);
        $board = Board::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Launch',
            'created_by' => $owner->id,
        ]);
        $list = $board->lists()->create([
            'name' => 'In progress',
            'position' => 1,
        ]);
        $dueThisWeek = Card::query()->create([
            'list_id' => $list->id,
            'title' => 'Due Friday',
            'due_date' => '2026-08-07 17:00:00',
            'created_by' => $owner->id,
        ]);
        Card::query()->create([
            'list_id' => $list->id,
            'title' => 'Due next week',
            'due_date' => '2026-08-12 17:00:00',
            'created_by' => $owner->id,
        ]);
        Card::query()->create([
            'list_id' => $list->id,
            'title' => 'No due date',
            'created_by' => $owner->id,
        ]);
        Card::query()->create([
            'list_id' => $list->id,
            'title' => 'Completed',
            'due_date' => '2026-08-06 17:00:00',
            'is_completed' => true,
            'created_by' => $owner->id,
        ]);
        Card::query()->create([
            'list_id' => $list->id,
            'title' => 'Archived',
            'is_archived' => true,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('overview.on_track_percent', 25)
                ->where('overview.open_tasks', 3)
                ->where('overview.open_tasks_percent', 75)
                ->where('overview.completed_tasks', 1)
                ->where('overview.due_this_week', 1)
                ->where('overview.due_this_week_percent', 33)
                ->where('overview.total_tasks', 4)
                ->where('overview.workspaces', 1)
                ->where('overview.boards', 1)
                ->where('overview.people', 2)
                ->where('ticketChart.0.value', 3)
                ->where('ticketChart.1.value', 1)
                ->where('ticketChart.2.value', 1)
            );

        $dueThisWeek->update(['is_completed' => true]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('overview.on_track_percent', 50)
                ->where('overview.open_tasks', 2)
                ->where('overview.completed_tasks', 2)
                ->where('overview.due_this_week', 0)
                ->where('ticketChart.0.value', 2)
                ->where('ticketChart.1.value', 2)
            );
    }

    public function test_dashboard_operational_stats_only_include_projects_the_user_can_access(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->for($company)->create(['role' => 'owner']);
        $guest = User::factory()->for($company)->create(['role' => 'guest']);
        $roles = app(RolePermissionDefaults::class)->ensureForCompany($company, $owner);

        $guest->forceFill(['role_id' => $roles->get('guest')?->id])->save();
        $guest->activeCompanyMembership()->update([
            'role_id' => $roles->get('guest')?->id,
            'is_company_wide' => false,
        ]);

        $visibleWorkspace = Workspace::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Visible workspace',
            'slug' => 'visible-workspace',
            'created_by' => $owner->id,
        ]);
        $visibleBoard = Board::query()->create([
            'workspace_id' => $visibleWorkspace->id,
            'name' => 'Visible board',
            'created_by' => $owner->id,
        ]);
        $visibleBoard->users()->attach($guest->id, ['role' => 'guest']);
        $visibleList = $visibleBoard->lists()->create(['name' => 'To do', 'position' => 1]);
        Card::query()->create([
            'list_id' => $visibleList->id,
            'title' => 'Visible task',
            'created_by' => $owner->id,
        ]);

        $hiddenWorkspace = Workspace::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Hidden workspace',
            'slug' => 'hidden-workspace',
            'created_by' => $owner->id,
        ]);
        $hiddenBoard = Board::query()->create([
            'workspace_id' => $hiddenWorkspace->id,
            'name' => 'Hidden board',
            'created_by' => $owner->id,
        ]);
        $hiddenList = $hiddenBoard->lists()->create(['name' => 'To do', 'position' => 1]);
        Card::query()->create([
            'list_id' => $hiddenList->id,
            'title' => 'Hidden task',
            'created_by' => $owner->id,
        ]);

        $this->actingAs($guest)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('overview.open_tasks', 1)
                ->where('overview.total_tasks', 1)
                ->where('overview.workspaces', 1)
                ->where('overview.boards', 1)
                ->where('overview.people', 0)
                ->where('ticketChart.0.value', 1)
            );
    }
}
