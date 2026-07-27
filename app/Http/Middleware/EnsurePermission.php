<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        abort_if(! $user instanceof User, 403);

        if (! $user->company_id) {
            return redirect()->route('company.setup');
        }

        abort_if(! $user->hasPermission($permission), 403);

        return $next($request);
    }
}
