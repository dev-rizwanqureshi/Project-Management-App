<?php

namespace App\Models\Scopes;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * @implements Scope<Workspace>
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! ($user instanceof User) || ! $user->company_id) {
            return;
        }

        $builder->where($model->getTable().'.company_id', $user->company_id);
    }
}
