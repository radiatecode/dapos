<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user->is_super_admin) {
            return $next($request);
        }

        $ability = Permission::tryFrom($permission);

        if (! $user instanceof User || $ability === null || ! $user->hasPermission($ability)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
