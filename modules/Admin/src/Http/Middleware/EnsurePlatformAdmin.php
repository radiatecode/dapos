<?php

namespace DA\Admin\Http\Middleware;

use Closure;
use DA\Admin\Models\AdminUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user('admin') instanceof AdminUser) {
            abort(403, 'This area is restricted to provider administrators.');
        }

        return $next($request);
    }
}
