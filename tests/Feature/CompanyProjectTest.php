<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\Board;
use App\Models\Card;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\Label;
use App\Models\TaskList;
use App\Models\User;
use App\Models\Workspace;
use App\Services\InvitationService;
use App\Services\RolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_workspaces_and_boards_with_a_default_workflow(): void
    {
        [$owner, $company] = $this->companyUser('owner');

        $this->actingAs($owner)
            ->post(route('workspaces.store'), [
                'name' => 'Client Delivery',
                'description' => 'Plan customer implementations.',
                'color' => '#0891b2',
            ])
            ->assertRedirect();

        $workspace = Workspace::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('slug', 'client-delivery')
            ->firstOrFail();

        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->post(route('boards.store'), [
                'workspace_id' => $workspace->id,
                'name' => 'Implementation board',
                'description' => 'Track delivery milestones.',
                'background' => '#dbeafe',
            ])
            ->assertRedirect();

        $board = Board::query()->where('workspace_id', $workspace->id)->firstOrFail();

        $this->assertSame(
            ['Backlog', 'To do', 'In progress', 'Done'],
            $board->lists()->orderBy('position')->pluck('name')->all(),
        );
        $this->assertDatabaseHas('board_user', [
            'board_id' => $board->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
    }

    public function test_board_page_exposes_context_and_supports_ticket_creation_and_movement(): void
    {
        [$owner, $company] = $this->companyUser('owner');
        [$workspace, $board, $backlog, $done] = $this->project($company, $owner);
        $label = Label::query()->create([
            'board_id' => $board->id,
            'name' => 'Launch',
            'color' => '#7c3aed',
        ]);

        $this->actingAs($owner)
            ->get(route('boards.show', $board))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Boards/Show')
                ->where('board.name', 'Delivery board')
                ->where('board.workspace.name', 'Delivery')
                ->where('board.labels.0.name', 'Launch')
                ->where('projectContext.company.name', $company->name)
                ->where('projectContext.workspaces.0.id', $workspace->id)
                ->where('projectContext.workspaces.0.boards.0.id', $board->id)
            );

        $this->actingAs($owner)
            ->post(route('boards.cards.store', $board), [
                'list_id' => $backlog->id,
                'title' => 'Confirm launch date',
                'description' => 'Align the final date with the customer.',
                'assignee_ids' => [$owner->id],
                'label_ids' => [$label->id],
            ])
            ->assertRedirect();

        $card = Card::query()->where('title', 'Confirm launch date')->firstOrFail();

        $this->assertDatabaseHas('card_label', [
            'card_id' => $card->id,
            'label_id' => $label->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'card_id' => $card->id,
            'action' => 'card.created',
        ]);

        $this->actingAs($owner)
            ->patch(route('boards.cards.move', [$board, $card]), [
                'list_id' => $done->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('toast.message', 'Ticket moved to Done.');

        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'list_id' => $done->id,
            'is_completed' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('boards.cards.comments.store', [$board, $card]), [
                'body' => 'The launch date is confirmed.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'card_id' => $card->id,
            'user_id' => $owner->id,
            'body' => 'The launch date is confirmed.',
        ]);

        $this->actingAs($owner)
            ->get(route('boards.cards.show', [$board, $card]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Boards/Show')
                ->where('ticket.id', $card->id)
                ->where('ticket.list.name', 'Done')
                ->where('ticket.comments.0.body', 'The launch date is confirmed.')
            );

        $this->actingAs($owner)
            ->get(route('boards.cards.show', [
                'board' => $board,
                'card' => $card,
                'fullscreen' => 1,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tickets/Show')
                ->where('board.id', $board->id)
                ->where('board.lists.1.name', 'Done')
                ->where('ticket.id', $card->id)
            );
    }

    public function test_member_cannot_create_workspaces_or_boards(): void
    {
        [$member, $company] = $this->companyUser('member');
        $workspace = Workspace::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Existing workspace',
            'slug' => 'existing-workspace',
            'created_by' => $member->id,
        ]);

        $this->actingAs($member)
            ->post(route('workspaces.store'), ['name' => 'Not allowed'])
            ->assertForbidden();

        $this->actingAs($member)
            ->post(route('boards.store'), [
                'workspace_id' => $workspace->id,
                'name' => 'Not allowed',
            ])
            ->assertForbidden();
    }

    public function test_viewer_cannot_create_or_move_tickets(): void
    {
        [$viewer, $company] = $this->companyUser('guest');
        [, $board, $backlog, $done] = $this->project($company, $viewer);
        $card = Card::query()->create([
            'list_id' => $backlog->id,
            'title' => 'Read-only ticket',
            'created_by' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->post(route('boards.cards.store', $board), [
                'list_id' => $backlog->id,
                'title' => 'Should be rejected',
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->patch(route('boards.cards.move', [$board, $card]), [
                'list_id' => $done->id,
            ])
            ->assertForbidden();
    }

    public function test_ticket_creation_rejects_foreign_assignees_and_labels(): void
    {
        [$owner, $company] = $this->companyUser('owner');
        [, $board, $backlog] = $this->project($company, $owner);
        [$foreignOwner, $foreignCompany] = $this->companyUser('owner');
        [, $foreignBoard] = $this->project($foreignCompany, $foreignOwner);
        $foreignLabel = Label::query()->create([
            'board_id' => $foreignBoard->id,
            'name' => 'Foreign',
            'color' => '#ef4444',
        ]);

        $this->actingAs($owner)
            ->post(route('boards.cards.store', $board), [
                'list_id' => $backlog->id,
                'title' => 'Must not be created',
                'assignee_ids' => [$foreignOwner->id],
                'label_ids' => [$foreignLabel->id],
            ])
            ->assertSessionHasErrors(['assignee_ids.0', 'label_ids.0']);

        $this->assertDatabaseMissing('cards', ['title' => 'Must not be created']);
    }

    public function test_ticket_reordering_persists_contiguous_positions(): void
    {
        [$owner, $company] = $this->companyUser('owner');
        [, $board, $backlog] = $this->project($company, $owner);
        $first = Card::query()->create([
            'list_id' => $backlog->id,
            'title' => 'First ticket',
            'position' => 1,
            'created_by' => $owner->id,
        ]);
        $second = Card::query()->create([
            'list_id' => $backlog->id,
            'title' => 'Second ticket',
            'position' => 2,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->patch(route('boards.cards.move', [$board, $second]), [
                'list_id' => $backlog->id,
                'position' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cards', [
            'id' => $second->id,
            'list_id' => $backlog->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('cards', [
            'id' => $first->id,
            'list_id' => $backlog->id,
            'position' => 2,
        ]);
    }

    public function test_owner_can_invite_a_company_admin_by_email(): void
    {
        Mail::fake();
        [$owner, $company] = $this->companyUser('owner');

        $this->actingAs($owner)
            ->post(route('invitations.store'), [
                'email' => 'admin@example.test',
                'scope' => 'company',
                'role' => 'admin',
            ])
            ->assertRedirect();

        $invitation = Invitation::query()->where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame($company->id, $invitation->company_id);
        $this->assertSame('admin', $invitation->role);
        $this->assertNull($invitation->workspace_id);
        $this->assertNull($invitation->board_id);
        Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail): bool => $mail->hasTo('admin@example.test')
            && $mail->invitation->is($invitation)
        );
    }

    public function test_workspace_invitation_can_create_an_account_and_join_the_workspace(): void
    {
        Mail::fake();
        [$owner, $company] = $this->companyUser('owner');
        [$workspace] = $this->project($company, $owner);
        $invitation = app(InvitationService::class)->create(
            $owner,
            $company,
            'new-member@example.test',
            'workspace',
            $workspace,
            null,
            'member',
        );

        $this->post(route('invitations.register', $invitation['token']), [
            'name' => 'New Member',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertRedirect(route('dashboard'));

        $newUser = User::query()->where('email', 'new-member@example.test')->firstOrFail();
        $this->assertSame($company->id, $newUser->company_id);
        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $workspace->id,
            'user_id' => $newUser->id,
            'role' => 'member',
        ]);
        $this->assertNotNull($invitation['invitation']->fresh()->accepted_at);
    }

    public function test_board_invitation_can_be_accepted_by_an_existing_unattached_user(): void
    {
        Mail::fake();
        [$owner, $company] = $this->companyUser('owner');
        [, $board] = $this->project($company, $owner);
        $invitee = User::factory()->create([
            'email' => 'board-member@example.test',
            'company_id' => null,
            'role' => 'member',
        ]);
        $invitation = app(InvitationService::class)->create(
            $owner,
            $company,
            $invitee->email,
            'board',
            $board->workspace,
            $board,
            'guest',
        );

        $this->actingAs($invitee)
            ->post(route('invitations.accept', $invitation['token']))
            ->assertRedirect(route('dashboard'));
        $invitee->refresh();

        $invitee->refresh();
        $this->assertSame($company->id, $invitee->company_id);
        $this->assertDatabaseHas('board_user', [
            'board_id' => $board->id,
            'user_id' => $invitee->id,
            'role' => 'guest',
        ]);
    }

    public function test_existing_company_user_gets_a_company_membership_and_can_be_promoted_by_invitation(): void
    {
        Mail::fake();
        [$owner, $company] = $this->companyUser('owner');
        $invitee = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'existing-admin@example.test',
            'role' => 'member',
        ]);

        $invitation = app(InvitationService::class)->create(
            $owner,
            $company,
            $invitee->email,
            'company',
            null,
            null,
            'admin',
        );

        $this->actingAs($invitee)
            ->post(route('invitations.accept', $invitation['token']))
            ->assertRedirect(route('dashboard'));
        $invitee->refresh();

        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $invitee->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
        $this->assertSame('admin', $invitee->fresh()->role);
        $this->assertSame(1, DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('user_id', $invitee->id)
            ->count());
    }

    public function test_existing_workspace_and_board_relationships_are_updated_without_duplicates(): void
    {
        Mail::fake();
        [$owner, $company] = $this->companyUser('owner');
        [$workspace, $board] = $this->project($company, $owner);
        $invitee = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'existing-board-member@example.test',
            'role' => 'member',
        ]);
        $workspace->users()->attach($invitee->id, ['role' => 'guest']);
        $board->users()->attach($invitee->id, ['role' => 'guest']);

        $invitation = app(InvitationService::class)->create(
            $owner,
            $company,
            $invitee->email,
            'board',
            $workspace,
            $board,
            'member',
        );

        $this->actingAs($invitee)
            ->post(route('invitations.accept', $invitation['token']))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $workspace->id,
            'user_id' => $invitee->id,
            'role' => 'member',
        ]);
        $this->assertDatabaseHas('board_user', [
            'board_id' => $board->id,
            'user_id' => $invitee->id,
            'role' => 'member',
        ]);
        $this->assertSame(1, DB::table('workspace_user')
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $invitee->id)
            ->count());
        $this->assertSame(1, DB::table('board_user')
            ->where('board_id', $board->id)
            ->where('user_id', $invitee->id)
            ->count());
    }

    public function test_board_invitation_limits_access_to_the_invited_board(): void
    {
        Mail::fake();
        [$owner, $company] = $this->companyUser('owner');
        [, $board] = $this->project($company, $owner);
        [, $otherBoard] = $this->project($company, $owner, 'Other', 'other');
        $invitee = User::factory()->create([
            'company_id' => null,
            'email' => 'scoped-board@example.test',
            'role' => 'member',
        ]);
        $invitation = app(InvitationService::class)->create(
            $owner,
            $company,
            $invitee->email,
            'board',
            $board->workspace,
            $board,
            'guest',
        );

        $this->actingAs($invitee)
            ->post(route('invitations.accept', $invitation['token']))
            ->assertRedirect(route('dashboard'));
        $invitee->refresh();

        $this->actingAs($invitee)
            ->get(route('boards.show', $board))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('board.id', $board->id)
                ->where('projectContext.workspaces.0.boards.0.id', $board->id)
            );

        $this->actingAs($invitee)
            ->get(route('boards.show', $otherBoard))
            ->assertNotFound();

        $this->actingAs($invitee)
            ->get(route('boards.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('boards.data', 1)
                ->where('boards.data.0.id', $board->id)
            );
    }

    public function test_workspace_invitation_limits_access_to_the_invited_workspace(): void
    {
        Mail::fake();
        [$owner, $company] = $this->companyUser('owner');
        [$workspace, $board] = $this->project($company, $owner);
        [, $otherBoard] = $this->project($company, $owner, 'Other', 'other');
        $invitee = User::factory()->create([
            'company_id' => null,
            'email' => 'scoped-workspace@example.test',
            'role' => 'member',
        ]);
        $invitation = app(InvitationService::class)->create(
            $owner,
            $company,
            $invitee->email,
            'workspace',
            $workspace,
            null,
            'member',
        );

        $this->actingAs($invitee)
            ->post(route('invitations.accept', $invitation['token']))
            ->assertRedirect(route('dashboard'));
        $invitee->refresh();

        $this->actingAs($invitee)
            ->get(route('boards.show', $board))
            ->assertOk();

        $this->actingAs($invitee)
            ->get(route('boards.show', $otherBoard))
            ->assertNotFound();

        $this->actingAs($invitee)
            ->get(route('workspaces.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('workspaces.data', 1)
                ->where('workspaces.data.0.id', $workspace->id)
            );
    }

    public function test_company_member_without_user_management_cannot_send_invitations(): void
    {
        Mail::fake();
        [$member] = $this->companyUser('member');

        $this->actingAs($member)
            ->post(route('invitations.store'), [
                'email' => 'blocked@example.test',
                'scope' => 'company',
                'role' => 'member',
            ])
            ->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_ticket_creation_accepts_and_secures_attachments(): void
    {
        Storage::fake('local');
        [$owner, $company] = $this->companyUser('owner');
        [, $board, $backlog] = $this->project($company, $owner);
        $file = UploadedFile::fake()->image('launch-plan.png');

        $this->actingAs($owner)
            ->post(route('boards.cards.store', $board), [
                'list_id' => $backlog->id,
                'title' => 'Attach launch plan',
                'attachments' => [$file],
            ])
            ->assertRedirect();

        $card = Card::query()->where('title', 'Attach launch plan')->firstOrFail();
        $attachment = $card->attachments()->firstOrFail();

        Storage::disk('local')->assertExists($attachment->file_path);
        $this->assertSame('launch-plan.png', $attachment->file_name);

        $this->actingAs($owner)
            ->get(route('boards.cards.attachments.download', [$board, $card, $attachment]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_company_user_cannot_open_a_board_from_another_company(): void
    {
        [$owner] = $this->companyUser('owner');
        [$otherOwner, $otherCompany] = $this->companyUser('owner');
        [, $foreignBoard] = $this->project($otherCompany, $otherOwner);

        $this->actingAs($owner)
            ->get(route('boards.show', $foreignBoard))
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function companyUser(string $role): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => $role,
        ]);
        $roles = app(RolePermissionDefaults::class)->ensureForCompany($company, $user);
        $user->forceFill(['role_id' => $roles->get($role)?->id])->save();

        return [$user->fresh(), $company];
    }

    /**
     * @return array{0: Workspace, 1: Board, 2: TaskList, 3: TaskList}
     */
    private function project(
        Company $company,
        User $owner,
        string $workspaceName = 'Delivery',
        string $workspaceSlug = 'delivery',
    ): array {
        $workspace = Workspace::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => $workspaceName,
            'slug' => $workspaceSlug,
            'color' => '#7c3aed',
            'created_by' => $owner->id,
        ]);
        $workspace->users()->attach($owner->id, ['role' => 'owner']);

        $board = Board::query()->create([
            'workspace_id' => $workspace->id,
            'name' => "{$workspaceName} board",
            'background' => '#ede9fe',
            'created_by' => $owner->id,
        ]);
        $board->users()->attach($owner->id, ['role' => 'owner']);
        $backlog = $board->lists()->create([
            'name' => 'Backlog',
            'position' => 1,
        ]);
        $done = $board->lists()->create([
            'name' => 'Done',
            'position' => 2,
        ]);

        return [$workspace, $board, $backlog, $done];
    }
}
