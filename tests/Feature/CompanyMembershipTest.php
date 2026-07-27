<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Card;
use App\Models\Company;
use App\Models\TaskList;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\CompanyMembershipRepositoryInterface;
use App\Services\RolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompanyMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_join_second_company_until_current_company_is_left(): void
    {
        $repository = app(CompanyMembershipRepositoryInterface::class);
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();
        $user = $this->activeUser($firstCompany, 'owner');
        $roles = app(RolePermissionDefaults::class)->ensureForCompany($secondCompany, $user);

        $this->expectException(ValidationException::class);

        $repository->joinCompany($user, $secondCompany, 'member', $roles->get('member'));
    }

    public function test_only_owner_cannot_leave_company(): void
    {
        $repository = app(CompanyMembershipRepositoryInterface::class);
        $company = Company::factory()->create();
        $owner = $this->activeUser($company, 'owner');

        $this->expectException(ValidationException::class);

        $repository->leaveActiveCompany($owner);
    }

    public function test_user_can_leave_company_when_another_owner_remains(): void
    {
        $repository = app(CompanyMembershipRepositoryInterface::class);
        $company = Company::factory()->create();
        $owner = $this->activeUser($company, 'owner');
        $this->activeUser($company, 'owner');
        $workspace = $this->workspace($company, $owner);
        $board = $this->board($workspace, $owner);
        $card = $this->card($board, $owner);

        $workspace->users()->attach($owner->id, ['role' => 'owner']);
        $board->users()->attach($owner->id, ['role' => 'owner']);
        $card->assignees()->attach($owner->id);

        $repository->leaveActiveCompany($owner);
        $owner->refresh();

        $this->assertNull($owner->company_id);
        $this->assertSame('member', $owner->role);
        $this->assertNull($owner->role_id);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'left',
        ]);
        $this->assertDatabaseMissing('workspace_user', [
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
        ]);
        $this->assertDatabaseMissing('board_user', [
            'board_id' => $board->id,
            'user_id' => $owner->id,
        ]);
        $this->assertDatabaseMissing('card_user', [
            'card_id' => $card->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_user_can_join_another_company_after_leaving_current_company(): void
    {
        $repository = app(CompanyMembershipRepositoryInterface::class);
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();
        $user = $this->activeUser($firstCompany, 'owner');
        $this->activeUser($firstCompany, 'owner');
        $roles = app(RolePermissionDefaults::class)->ensureForCompany($secondCompany, $user);

        $repository->leaveActiveCompany($user);
        $repository->joinCompany($user, $secondCompany, 'admin', $roles->get('admin'));
        $user->refresh();

        $this->assertSame($secondCompany->id, $user->company_id);
        $this->assertSame('admin', $user->role);
        $this->assertSame($roles->get('admin')?->id, $user->role_id);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $firstCompany->id,
            'user_id' => $user->id,
            'status' => 'left',
        ]);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $secondCompany->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function activeUser(Company $company, string $role): User
    {
        $user = User::factory()->create([
            'company_id' => null,
            'role' => 'member',
            'role_id' => null,
        ]);
        $roles = app(RolePermissionDefaults::class)->ensureForCompany($company, $user);

        app(CompanyMembershipRepositoryInterface::class)->joinCompany($user, $company, $role, $roles->get($role));

        return $user->refresh();
    }

    private function workspace(Company $company, User $creator): Workspace
    {
        return Workspace::query()->create([
            'company_id' => $company->id,
            'name' => 'Product',
            'slug' => 'product',
            'created_by' => $creator->id,
        ]);
    }

    private function board(Workspace $workspace, User $creator): Board
    {
        return Board::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Launch',
            'created_by' => $creator->id,
        ]);
    }

    private function card(Board $board, User $creator): Card
    {
        $list = TaskList::query()->create([
            'board_id' => $board->id,
            'name' => 'To Do',
            'position' => 1,
        ]);

        return Card::query()->create([
            'list_id' => $list->id,
            'title' => 'Prepare launch',
            'created_by' => $creator->id,
        ]);
    }
}
