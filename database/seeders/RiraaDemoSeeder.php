<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Company;
use App\Models\Label;
use App\Models\TaskList;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\CompanyMembershipRepositoryInterface;
use App\Services\AdminRolePermissionDefaults;
use App\Services\RolePermissionDefaults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RiraaDemoSeeder extends Seeder
{
    /**
     * Seed a complete demo dataset for the Riraa project.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $company = Company::withTrashed()->updateOrCreate(
                ['slug' => 'riraa'],
                [
                    'name' => 'Riraa',
                    'email' => 'hello@riraa.com',
                    'phone' => '+1 555 014 8720',
                    'website' => 'https://riraa.com',
                    'industry' => 'Project management software',
                    'team_size' => '11-50',
                    'address_line' => '100 Product Avenue',
                    'city' => 'Austin',
                    'state' => 'Texas',
                    'country' => 'United States',
                    'postal_code' => '78701',
                    'timezone' => 'America/Chicago',
                    'description' => 'Riraa helps teams plan workspaces, boards, and tickets in one place.',
                    'logo' => null,
                    'trial_ends_at' => now()->addDays(14),
                ],
            );
            $company->restore();

            $owner = $this->user('Riraa Owner', 'examples@riraa.com', 'owner', $company);
            $admin = $this->user('Riraa Admin', 'admin@riraa.com', 'admin', $company);
            $member = $this->user('Riraa Member', 'member@riraa.com', 'member', $company);
            $roles = app(RolePermissionDefaults::class)->ensureForCompany($company, $owner);
            $companyMemberships = app(CompanyMembershipRepositoryInterface::class);

            foreach ([$owner, $admin, $member] as $user) {
                $companyMemberships->joinCompany($user, $company, $user->role, $roles->get($user->role));
            }

            $this->normalizeLegacyAdmin('superadmin@riraa.com', 'admin@riraa.com', 'Riraa Admin', 'admin');

            $adminOwner = $this->admin('Riraa Platform Owner', 'owner@riraa.com', 'owner');
            $adminStaff = $this->admin('Riraa Admin', 'admin@riraa.com', 'admin');
            $supportAdmin = $this->admin('Riraa Support Staff', 'support@riraa.com', 'support_staff');
            $adminRoles = app(AdminRolePermissionDefaults::class)->ensureRoles($adminOwner);

            foreach ([$adminOwner, $adminStaff, $supportAdmin] as $adminUser) {
                $adminUser->forceFill(['admin_role_id' => $adminRoles->get($adminUser->role)?->id])->save();
            }

            $workspace = Workspace::withTrashed()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => 'riraa-product',
                ],
                [
                    'name' => 'Riraa Product',
                    'description' => 'Demo workspace for validating the full project management schema.',
                    'color' => '#2563eb',
                    'created_by' => $owner->id,
                ],
            );
            $workspace->restore();

            $workspace->users()->syncWithoutDetaching([
                $owner->id => ['role' => 'owner'],
                $admin->id => ['role' => 'admin'],
                $member->id => ['role' => 'member'],
            ]);

            $board = Board::withTrashed()->updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'name' => 'Riraa Launch Board',
                ],
                [
                    'description' => 'Sample launch workflow for checking boards, lists, cards, labels, and activity.',
                    'background' => '#0f172a',
                    'is_private' => false,
                    'is_archived' => false,
                    'created_by' => $owner->id,
                ],
            );
            $board->restore();

            $board->users()->syncWithoutDetaching([
                $owner->id => ['role' => 'owner'],
                $admin->id => ['role' => 'admin'],
                $member->id => ['role' => 'member'],
            ]);

            $todo = $this->taskList($board, 'To Do', 1);
            $doing = $this->taskList($board, 'In Progress', 2);
            $done = $this->taskList($board, 'Done', 3);

            $featureLabel = $this->label($board, 'Feature', '#16a34a');
            $urgentLabel = $this->label($board, 'Urgent', '#dc2626');
            $designLabel = $this->label($board, 'Design', '#7c3aed');

            $setupCard = $this->card(
                $todo,
                'Set up Riraa workspace',
                'Create the first workspace, invite the team, and verify the board workflow.',
                1,
                $owner,
                false,
            );

            $reviewCard = $this->card(
                $doing,
                'Review authentication flow',
                'Confirm company registration, login, profile fetch, and logout with session auth.',
                1,
                $admin,
                false,
            );

            $doneCard = $this->card(
                $done,
                'Create database schema',
                'Migrations and core relationships are ready for the project management module.',
                1,
                $owner,
                true,
            );

            $setupCard->labels()->syncWithoutDetaching([$featureLabel->id, $urgentLabel->id]);
            $reviewCard->labels()->syncWithoutDetaching([$featureLabel->id]);
            $doneCard->labels()->syncWithoutDetaching([$designLabel->id]);

            $setupCard->assignees()->syncWithoutDetaching([$owner->id, $admin->id]);
            $reviewCard->assignees()->syncWithoutDetaching([$admin->id, $member->id]);
            $doneCard->assignees()->syncWithoutDetaching([$owner->id]);

            $checklist = Checklist::query()->updateOrCreate(
                [
                    'card_id' => $setupCard->id,
                    'title' => 'Launch checklist',
                ],
                ['position' => 1],
            );

            ChecklistItem::query()->updateOrCreate(
                [
                    'checklist_id' => $checklist->id,
                    'title' => 'Create owner, admin, and member users',
                ],
                [
                    'is_completed' => true,
                    'position' => 1,
                ],
            );

            ChecklistItem::query()->updateOrCreate(
                [
                    'checklist_id' => $checklist->id,
                    'title' => 'Verify seed data across all project tables',
                ],
                [
                    'is_completed' => false,
                    'position' => 2,
                ],
            );

            $comment = Comment::withTrashed()->updateOrCreate(
                [
                    'card_id' => $setupCard->id,
                    'user_id' => $owner->id,
                    'body' => 'Riraa demo data is ready for a full database check.',
                ],
                [],
            );
            $comment->restore();

            Attachment::query()->updateOrCreate(
                [
                    'card_id' => $setupCard->id,
                    'file_path' => 'attachments/riraa/project-brief.pdf',
                ],
                [
                    'user_id' => $admin->id,
                    'file_name' => 'project-brief.pdf',
                    'file_type' => 'application/pdf',
                    'file_size' => 245760,
                ],
            );

            ActivityLog::query()->updateOrCreate(
                [
                    'board_id' => $board->id,
                    'card_id' => $setupCard->id,
                    'action' => 'card.created',
                ],
                [
                    'user_id' => $owner->id,
                    'description' => 'Created the Riraa setup card.',
                    'properties' => [
                        'card_title' => $setupCard->title,
                        'list_name' => $todo->name,
                    ],
                    'created_at' => now()->subHours(2),
                ],
            );

            ActivityLog::query()->updateOrCreate(
                [
                    'board_id' => $board->id,
                    'card_id' => $reviewCard->id,
                    'action' => 'card.assigned',
                ],
                [
                    'user_id' => $admin->id,
                    'description' => 'Assigned review work to the team.',
                    'properties' => [
                        'assignees' => [$admin->email, $member->email],
                    ],
                    'created_at' => now()->subHour(),
                ],
            );

            DB::table('notifications')->updateOrInsert(
                ['id' => '00000000-0000-4000-8000-000000000001'],
                [
                    'type' => 'database',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $owner->id,
                    'data' => json_encode([
                        'title' => 'Welcome to Riraa',
                        'body' => 'Your demo company and project data are ready.',
                    ]),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        });
    }

    private function user(string $name, string $email, string $role, Company $company): User
    {
        $user = User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'company_id' => $company->id,
                'name' => $name,
                'password' => Hash::make('password'),
                'avatar' => null,
                'role' => $role,
            ],
        );

        $user->restore();
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function admin(string $name, string $email, string $role): Admin
    {
        return Admin::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => $role,
            ],
        );
    }

    private function normalizeLegacyAdmin(string $legacyEmail, string $email, string $name, string $role): void
    {
        $legacy = Admin::query()->where('email', $legacyEmail)->first();

        if (! $legacy) {
            return;
        }

        $current = Admin::query()->where('email', $email)->first();

        $legacy->forceFill([
            'name' => $name,
            'email' => $current && $current->id !== $legacy->id ? $legacyEmail : $email,
            'role' => $role,
        ])->save();
    }

    private function taskList(Board $board, string $name, int $position): TaskList
    {
        return TaskList::query()->updateOrCreate(
            [
                'board_id' => $board->id,
                'name' => $name,
            ],
            [
                'position' => $position,
                'is_archived' => false,
            ],
        );
    }

    private function label(Board $board, string $name, string $color): Label
    {
        return Label::query()->updateOrCreate(
            [
                'board_id' => $board->id,
                'name' => $name,
            ],
            ['color' => $color],
        );
    }

    private function card(
        TaskList $list,
        string $title,
        string $description,
        int $position,
        User $creator,
        bool $isCompleted,
    ): Card {
        $card = Card::withTrashed()->updateOrCreate(
            [
                'list_id' => $list->id,
                'title' => $title,
            ],
            [
                'description' => $description,
                'position' => $position,
                'cover_image' => null,
                'start_date' => now()->startOfDay(),
                'due_date' => now()->addDays(7)->endOfDay(),
                'is_completed' => $isCompleted,
                'is_archived' => false,
                'created_by' => $creator->id,
            ],
        );
        $card->restore();

        return $card;
    }
}
