<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminPermission extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'name',
        'slug',
        'description',
    ];

    /**
     * @return BelongsToMany<AdminRole, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_permission_role')
            ->withTimestamps();
    }
}
