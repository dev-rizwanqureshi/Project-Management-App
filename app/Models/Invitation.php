<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $workspace_id
 * @property int|null $board_id
 * @property int $invited_by
 * @property string $email
 * @property string $role
 * @property string $token
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 */
class Invitation extends Model
{
    protected $fillable = [
        'company_id',
        'workspace_id',
        'board_id',
        'invited_by',
        'email',
        'role',
        'token',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Board, $this> */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    /**
     * @param  Builder<Invitation>  $query
     * @return Builder<Invitation>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    /**
     * @param  Builder<Invitation>  $query
     * @return Builder<Invitation>
     */
    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', mb_strtolower(trim($email)));
    }

    public function scopeLabel(): string
    {
        return match (true) {
            $this->board_id !== null => 'board',
            $this->workspace_id !== null => 'workspace',
            default => 'company',
        };
    }
}
