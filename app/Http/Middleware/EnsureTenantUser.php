<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use DA\Admin\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUser
{
    public function __construct(private TenantContext $tenantContext) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isTenantUser() || ! $this->tenantContext->has()) {
            abort(403, 'This area is restricted to tenant users.');
        }

        return $next($request);
    }
}
