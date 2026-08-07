<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\MoveCardRequest;
use App\Http\Requests\Project\StoreBoardRequest;
use App\Http\Requests\Project\StoreCardRequest;
use App\Http\Requests\Project\StoreCommentRequest;
use App\Http\Requests\Project\StoreWorkspaceRequest;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\TaskList;
use App\Models\User;
use App\Models\Workspace;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyProjectController extends Controller
{
    public function storeWorkspace(StoreWorkspaceRequest $request): RedirectResponse
    {
        $user = $this->user($request);
        Gate::forUser($user)->authorize('create', Workspace::class);
        $validated = $request->validated();

        $workspace = DB::transaction(function () use ($user, $validated): Workspace {
            $workspace = Workspace::withoutGlobalScopes()->create([
                'company_id' => $user->company_id,
                'name' => trim((string) $validated['name']),
                'slug' => $this->uniqueWorkspaceSlug($user, (string) $validated['name']),
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? '#7c3aed',
                'created_by' => $user->id,
            ]);

            $workspace->users()->attach($user->id, [
                'role' => $user->role === 'owner' ? 'owner' : 'admin',
            ]);

            return $workspace;
        });

        return redirect()
            ->route('boards.index', ['workspace_id' => $workspace->id])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Workspace created. Add its first board when you are ready.',
            ]);
    }

    public function storeBoard(StoreBoardRequest $request): RedirectResponse
    {
        $user = $this->user($request);
        $validated = $request->validated();

        $workspace = Workspace::query()
            ->whereKey((int) $validated['workspace_id'])
            ->where('company_id', $user->company_id)
            ->where('is_restricted', false)
            ->firstOrFail();
        Gate::forUser($user)->authorize('create', [Board::class, $workspace]);

        $board = DB::transaction(function () use ($user, $workspace, $validated): Board {
            $board = Board::query()->create([
                'workspace_id' => $workspace->id,
                'name' => trim((string) $validated['name']),
                'description' => $validated['description'] ?? null,
                'background' => $validated['background'] ?? '#f5f3ff',
                'is_private' => false,
                'is_archived' => false,
                'created_by' => $user->id,
            ]);

            $board->users()->attach($user->id, [
                'role' => $user->role === 'owner' ? 'owner' : 'admin',
            ]);

            foreach (['Backlog', 'To do', 'In progress', 'Done'] as $position => $name) {
                $board->lists()->create([
                    'name' => $name,
                    'position' => $position + 1,
                    'is_archived' => false,
                ]);
            }

            return $board;
        });

        return redirect()
            ->route('boards.show', $board)
            ->with('toast', [
                'type' => 'success',
                'message' => 'Board created successfully.',
            ]);
    }

    public function showBoard(Request $request, Board $board): Response
    {
        $user = $this->user($request);
        $this->assertBoardBelongsToUserCompany($user, $board);
        Gate::forUser($user)->authorize('view', $board);

        return Inertia::render('Boards/Show', [
            'board' => $this->boardPayload($board),
            'ticket' => null,
        ]);
    }

    public function showCard(Request $request, Board $board, Card $card): Response
    {
        $user = $this->user($request);
        $this->assertBoardBelongsToUserCompany($user, $board);
        $this->assertCardBelongsToBoard($card, $board);
        Gate::forUser($user)->authorize('view', $card);

        $ticket = $this->ticketPayload($card);

        if ($request->boolean('fullscreen')) {
            return Inertia::render('Tickets/Show', [
                'board' => [
                    'id' => $board->id,
                    'name' => $board->name,
                    'workspace' => [
                        'id' => $board->workspace->id,
                        'name' => $board->workspace->name,
                    ],
                    'lists' => $board->lists()
                        ->where('is_archived', false)
                        ->orderBy('position')
                        ->get(['id', 'name'])
                        ->map(fn (TaskList $list): array => [
                            'id' => $list->id,
                            'name' => $list->name,
                        ])
                        ->values()
                        ->all(),
                ],
                'ticket' => $ticket,
            ]);
        }

        return Inertia::render('Boards/Show', [
            'board' => $this->boardPayload($board),
            'ticket' => $ticket,
        ]);
    }

    public function storeComment(StoreCommentRequest $request, Board $board, Card $card): RedirectResponse
    {
        $user = $this->user($request);
        $this->assertBoardBelongsToUserCompany($user, $board);
        $this->assertCardBelongsToBoard($card, $board);
        Gate::forUser($user)->authorize('comment', $card);
        $validated = $request->validated();

        DB::transaction(function () use ($user, $board, $card, $validated): void {
            $card->comments()->create([
                'user_id' => $user->id,
                'body' => trim((string) $validated['body']),
            ]);

            $board->activityLogs()->create([
                'card_id' => $card->id,
                'user_id' => $user->id,
                'action' => 'card.commented',
                'description' => 'Added a comment.',
                'created_at' => now(),
            ]);
        });

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Comment added.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function boardPayload(Board $board): array
    {
        $board->load([
            'workspace:id,company_id,name,slug,color',
            'users:id,name,email,avatar',
            'labels:id,board_id,name,color',
            'lists' => fn ($query) => $query
                ->where('is_archived', false)
                ->orderBy('position')
                ->with([
                    'cards' => fn ($cardQuery) => $cardQuery
                        ->where('is_restricted', false)
                        ->where('is_archived', false)
                        ->with(['labels:id,name,color', 'assignees:id,name,email,avatar'])
                        ->withCount(['comments', 'attachments'])
                        ->orderBy('position'),
                ]),
        ])->loadCount('users');

        $members = [];
        foreach ($board->users as $member) {
            $members[] = $this->personPayload($member);
        }

        $boardLabels = [];
        foreach ($board->labels as $label) {
            $boardLabels[] = [
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
            ];
        }

        $lists = [];
        foreach ($board->lists as $list) {
            $cards = [];

            foreach ($list->cards as $card) {
                $labels = [];
                foreach ($card->labels as $label) {
                    $labels[] = [
                        'id' => $label->id,
                        'name' => $label->name,
                        'color' => $label->color,
                    ];
                }

                $assignees = [];
                foreach ($card->assignees as $assignee) {
                    $assignees[] = $this->personPayload($assignee);
                }

                $cards[] = [
                    'id' => $card->id,
                    'title' => $card->title,
                    'position' => (int) $card->position,
                    'description' => $card->description,
                    'due_date' => $this->isoDate($card->due_date),
                    'is_completed' => $card->is_completed,
                    'comments_count' => (int) $card->getAttribute('comments_count'),
                    'attachments_count' => (int) $card->getAttribute('attachments_count'),
                    'labels' => $labels,
                    'assignees' => $assignees,
                ];
            }

            $lists[] = [
                'id' => $list->id,
                'name' => $list->name,
                'position' => $list->position,
                'cards' => $cards,
            ];
        }

        return [
            'id' => $board->id,
            'name' => $board->name,
            'description' => $board->description,
            'background' => $board->background,
            'workspace' => [
                'id' => $board->workspace->id,
                'name' => $board->workspace->name,
                'slug' => $board->workspace->slug,
                'color' => $board->workspace->color,
            ],
            'members' => $members,
            'labels' => $boardLabels,
            'lists' => $lists,
            'users_count' => (int) $board->getAttribute('users_count'),
        ];
    }

    public function storeCard(StoreCardRequest $request, Board $board): RedirectResponse
    {
        $user = $this->user($request);
        $this->assertBoardBelongsToUserCompany($user, $board);
        Gate::forUser($user)->authorize('create', [Card::class, $board]);
        $validated = $request->validated();
        /** @var array<int, UploadedFile> $attachments */
        $attachments = array_values(array_filter(
            (array) $request->file('attachments', []),
            static fn (mixed $file): bool => $file instanceof UploadedFile,
        ));

        $list = $board->lists()->whereKey((int) $validated['list_id'])->firstOrFail();
        $assigneeIds = array_values($validated['assignee_ids'] ?? [$user->id]);
        $labelIds = array_values($validated['label_ids'] ?? []);

        DB::transaction(function () use ($user, $board, $list, $validated, $assigneeIds, $labelIds, $attachments): void {
            $card = $list->cards()->create([
                'title' => trim((string) $validated['title']),
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'position' => ((int) $list->cards()->lockForUpdate()->max('position')) + 1,
                'is_completed' => Str::lower($list->name) === 'done',
                'is_archived' => false,
                'created_by' => $user->id,
            ]);

            $card->assignees()->sync($assigneeIds);
            $card->labels()->sync($labelIds);

            foreach ($attachments as $file) {
                $path = $file->store("attachments/{$card->id}", 'local');

                $card->attachments()->create([
                    'user_id' => $user->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            $board->activityLogs()->create([
                'card_id' => $card->id,
                'user_id' => $user->id,
                'action' => 'card.created',
                'description' => 'Created the ticket.',
                'created_at' => now(),
            ]);
        });

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Ticket added to the board.',
        ]);
    }

    public function downloadAttachment(
        Request $request,
        Board $board,
        Card $card,
        Attachment $attachment,
    ): StreamedResponse {
        $user = $this->user($request);
        $this->assertBoardBelongsToUserCompany($user, $board);
        $this->assertCardBelongsToBoard($card, $board);
        Gate::forUser($user)->authorize('view', $card);
        abort_unless($attachment->card_id === $card->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->file_name,
            ['Content-Type' => $attachment->file_type ?? 'application/octet-stream'],
        );
    }

    public function moveCard(MoveCardRequest $request, Board $board, Card $card): RedirectResponse
    {
        $user = $this->user($request);
        $this->assertBoardBelongsToUserCompany($user, $board);
        $this->assertCardBelongsToBoard($card, $board);
        Gate::forUser($user)->authorize('move', $card);

        $validated = $request->validated();
        $destination = $board->lists()->whereKey((int) $validated['list_id'])->firstOrFail();

        DB::transaction(function () use ($user, $board, $card, $destination, $validated): void {
            $lockedCard = Card::query()->whereKey($card->id)->lockForUpdate()->firstOrFail();
            $sourceListId = $lockedCard->list_id;
            $sourceCards = Card::query()
                ->where('list_id', $sourceListId)
                ->lockForUpdate()
                ->orderBy('position')
                ->orderBy('id')
                ->get(['id']);
            $destinationCards = $sourceListId === $destination->id
                ? collect()
                : Card::query()
                    ->where('list_id', $destination->id)
                    ->lockForUpdate()
                    ->orderBy('position')
                    ->orderBy('id')
                    ->get(['id']);

            $sourceIds = $sourceCards
                ->pluck('id')
                ->reject(fn (int $id): bool => $id === $lockedCard->id)
                ->values()
                ->all();
            $destinationIds = $sourceListId === $destination->id
                ? $sourceIds
                : $destinationCards->pluck('id')->values()->all();
            $requestedPosition = (int) ($validated['position'] ?? count($destinationIds) + 1);
            $targetPosition = max(1, min($requestedPosition, count($destinationIds) + 1));

            array_splice($destinationIds, $targetPosition - 1, 0, [$lockedCard->id]);

            foreach ($sourceIds as $position => $cardId) {
                Card::query()->whereKey($cardId)->update(['position' => $position + 1]);
            }

            foreach ($destinationIds as $position => $cardId) {
                Card::query()->whereKey($cardId)->update([
                    'list_id' => $destination->id,
                    'position' => $position + 1,
                ]);
            }

            $lockedCard->update([
                'list_id' => $destination->id,
                'position' => $targetPosition,
                'is_completed' => Str::lower($destination->name) === 'done',
            ]);

            $board->activityLogs()->create([
                'card_id' => $lockedCard->id,
                'user_id' => $user->id,
                'action' => 'card.moved',
                'description' => "Moved ticket to {$destination->name}.",
                'properties' => ['position' => $targetPosition],
                'created_at' => now(),
            ]);
        });

        return back()->with('toast', [
            'type' => 'success',
            'message' => "Ticket moved to {$destination->name}.",
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->company_id, 403);

        return $user;
    }

    private function assertBoardBelongsToUserCompany(User $user, Board $board): void
    {
        $board->loadMissing('workspace');

        abort_unless(
            $board->workspace
                && $board->workspace->company_id === $user->company_id
                && ! $board->is_restricted
                && ! $board->is_archived
                && ! $board->workspace->is_restricted
                && $user->hasBoardAccess($board),
            404,
        );
    }

    private function assertCardBelongsToBoard(Card $card, Board $board): void
    {
        abort_unless(
            $card->list?->board_id === $board->id
                && ! $card->is_restricted
                && ! $card->is_archived,
            404,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function ticketPayload(Card $card): array
    {
        $card->load([
            'list:id,board_id,name',
            'creator:id,name,email,avatar',
            'assignees:id,name,email,avatar',
            'labels:id,name,color',
            'checklists' => fn ($query) => $query
                ->orderBy('position')
                ->with(['items' => fn ($itemQuery) => $itemQuery->orderBy('position')]),
            'attachments' => fn ($query) => $query
                ->with('user:id,name,email,avatar')
                ->latest(),
            'comments' => fn ($query) => $query
                ->with('user:id,name,email,avatar')
                ->oldest(),
            'activityLogs' => fn ($query) => $query
                ->with('user:id,name,email,avatar')
                ->latest('created_at'),
        ]);

        $assignees = [];
        foreach ($card->assignees as $assignee) {
            $assignees[] = $this->personPayload($assignee);
        }

        $labels = [];
        foreach ($card->labels as $label) {
            $labels[] = [
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
            ];
        }

        $checklists = [];
        foreach ($card->checklists as $checklist) {
            $items = [];
            foreach ($checklist->items as $item) {
                $items[] = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'is_completed' => $item->is_completed,
                ];
            }

            $checklists[] = [
                'id' => $checklist->id,
                'title' => $checklist->title,
                'items' => $items,
            ];
        }

        $attachments = [];
        foreach ($card->attachments as $attachment) {
            $attachments[] = [
                'id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'file_type' => $attachment->file_type,
                'file_size' => $attachment->file_size,
                'download_url' => route('boards.cards.attachments.download', [
                    'board' => $card->list->board_id,
                    'card' => $card->id,
                    'attachment' => $attachment->id,
                ]),
                'created_at' => $this->isoDate($attachment->created_at),
                'user' => $attachment->user ? $this->personPayload($attachment->user) : null,
            ];
        }

        $comments = [];
        foreach ($card->comments as $comment) {
            $comments[] = [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $this->isoDate($comment->created_at),
                'user' => $comment->user ? $this->personPayload($comment->user) : null,
            ];
        }

        $activity = [];
        foreach ($card->activityLogs as $activityLog) {
            $activity[] = [
                'id' => $activityLog->id,
                'action' => $activityLog->action,
                'description' => $activityLog->description,
                'created_at' => $this->isoDate($activityLog->created_at),
                'user' => $activityLog->user ? $this->personPayload($activityLog->user) : null,
            ];
        }

        return [
            'id' => $card->id,
            'key' => 'RIR-'.str_pad((string) $card->id, 3, '0', STR_PAD_LEFT),
            'title' => $card->title,
            'description' => $card->description,
            'start_date' => $this->isoDate($card->start_date),
            'due_date' => $this->isoDate($card->due_date),
            'is_completed' => $card->is_completed,
            'created_at' => $card->created_at?->toISOString(),
            'updated_at' => $card->updated_at?->toISOString(),
            'list' => [
                'id' => $card->list->id,
                'name' => $card->list->name,
            ],
            'creator' => $card->creator ? $this->personPayload($card->creator) : null,
            'assignees' => $assignees,
            'labels' => $labels,
            'checklists' => $checklists,
            'attachments' => $attachments,
            'comments' => $comments,
            'activity' => $activity,
        ];
    }

    private function isoDate(string|DateTimeInterface|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if ($date instanceof DateTimeInterface) {
            return $date->format(DateTimeInterface::ATOM);
        }

        $parsedDate = date_create_immutable($date);

        return $parsedDate === false ? null : $parsedDate->format(DateTimeInterface::ATOM);
    }

    /**
     * @return array{id: int, name: string, email: string, avatar: string|null}
     */
    private function personPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
        ];
    }

    private function uniqueWorkspaceSlug(User $user, string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $suffix = 2;

        while (Workspace::withoutGlobalScopes()
            ->withTrashed()
            ->where('company_id', $user->company_id)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
