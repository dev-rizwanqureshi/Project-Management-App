<?php

namespace App\Models;

use App\Services\AdminRolePermissionDefaults;
use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'admin_role_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<AdminRole, $this>
     */
    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class);
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
            $permissions = AdminPermission::query()
                ->pluck('slug')
                ->map(fn (mixed $slug): string => (string) $slug)
                ->all();

            return $permissions !== []
                ? array_values($permissions)
                : app(AdminRolePermissionDefaults::class)->permissionSlugs();
        }

        $role = $this->adminRole;

        if (! $role) {
            $role = AdminRole::query()
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
