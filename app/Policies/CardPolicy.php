<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\Card;
use App\Models\User;

class CardPolicy
{
    public function view(User $user, Card $card): bool
    {
        $card->loadMissing('list.board.workspace');

        return $this->activeCardInCompany($user, $card)
            && $this->cardIsAccessibleToUser($user, $card)
            && $user->hasPermission('cards.view');
    }

    public function create(User $user, Board $board): bool
    {
        $board->loadMissing('workspace');

        return $board->workspace
            && $this->sameCompany($user, $board->workspace->company_id)
            && $user->hasBoardAccess($board)
            && ! $board->is_restricted
            && ! $board->is_archived
            && ! $board->workspace->is_restricted
            && $user->hasPermission('cards.manage');
    }

    public function move(User $user, Card $card): bool
    {
        return $this->manage($user, $card);
    }

    public function comment(User $user, Card $card): bool
    {
        return $this->manage($user, $card);
    }

    private function manage(User $user, Card $card): bool
    {
        $card->loadMissing('list.board.workspace');

        return $this->activeCardInCompany($user, $card)
            && $this->cardIsAccessibleToUser($user, $card)
            && $user->hasPermission('cards.manage');
    }

    private function cardIsAccessibleToUser(User $user, Card $card): bool
    {
        $card->loadMissing('list.board.workspace');

        return $card->list?->board !== null
            && $user->hasBoardAccess($card->list->board);
    }

    private function activeCardInCompany(User $user, Card $card): bool
    {
        return $card->list?->board?->workspace
            && $this->sameCompany($user, $card->list->board->workspace->company_id)
            && ! $card->is_restricted
            && ! $card->is_archived
            && ! $card->list->is_archived
            && ! $card->list->board->is_restricted
            && ! $card->list->board->is_archived
            && ! $card->list->board->workspace->is_restricted;
    }

    private function sameCompany(User $user, int $companyId): bool
    {
        return $user->company_id === $companyId;
    }
}
