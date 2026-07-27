<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\RolePermissionDefaults;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property bool $is_restricted
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'role_id',
        'is_restricted',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_restricted' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<CompanyUser, $this>
     */
    public function companyMemberships(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    /**
     * @return BelongsToMany<Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['role', 'role_id', 'status', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Company, $this>
     */
    public function activeCompanies(): BelongsToMany
    {
        return $this->companies()->wherePivot('status', 'active');
    }

    /**
     * @return HasOne<CompanyUser, $this>
     */
    public function activeCompanyMembership(): HasOne
    {
        return $this->hasOne(CompanyUser::class)->where('status', 'active');
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function roleDefinition(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * @return BelongsToMany<Workspace, $this>
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Workspace, $this>
     */
    public function createdWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'created_by');
    }

    /**
     * @return BelongsToMany<Board, $this>
     */
    public function boards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Board, $this>
     */
    public function createdBoards(): HasMany
    {
        return $this->hasMany(Board::class, 'created_by');
    }

    /**
     * @return BelongsToMany<Card, $this>
     */
    public function assignedCards(): BelongsToMany
    {
        return $this->belongsToMany(Card::class)
            ->withTimestamps();
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function createdCards(): HasMany
    {
        return $this->hasMany(Card::class, 'created_by');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'owner') {
            return true;
        }

        return in_array($permission, $this->permissionSlugs(), true);
    }

    /**
     * @return list<string>
     */
    public function permissionSlugs(): array
    {
        if ($this->role === 'owner') {
            $permissions = Permission::query()
                ->pluck('slug')
                ->map(fn (mixed $slug): string => (string) $slug)
                ->all();

            return $permissions !== []
                ? array_values($permissions)
                : app(RolePermissionDefaults::class)->permissionSlugs();
        }

        $role = $this->roleDefinition;

        if (! $role && $this->company_id) {
            $role = Role::query()
                ->where('company_id', $this->company_id)
                ->where('slug', $this->role)
                ->first();
        }

        if (! $role) {
            return [];
        }

        return array_values($role->permissions()
            ->pluck('slug')
            ->map(fn (mixed $slug): string => (string) $slug)
            ->all());
    }
}
