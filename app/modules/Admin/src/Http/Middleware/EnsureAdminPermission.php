<?php

namespace DA\Admin\Http\Middleware;

use Closure;
use DA\Admin\Enums\AdminPermission;
use DA\Admin\Models\AdminUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = $request->user('admin');
        $ability = AdminPermission::tryFrom($permission);

        if (! $admin instanceof AdminUser || $ability === null || $admin->hasAdminPermission($ability) !== true) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
