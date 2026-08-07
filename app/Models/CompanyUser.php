<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property string $role
 * @property int|null $role_id
 * @property string $status
 * @property bool $is_company_wide
 * @property Carbon|null $joined_at
 * @property Carbon|null $left_at
 */
class CompanyUser extends Model
{
    protected $table = 'company_user';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'role',
        'role_id',
        'status',
        'is_company_wide',
        'joined_at',
        'left_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'is_company_wide' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function roleDefinition(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
