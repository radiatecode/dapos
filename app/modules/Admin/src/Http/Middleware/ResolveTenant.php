<?php

namespace DA\Admin\Http\Middleware;

use App\Enums\TenantStatus;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(private TenantContext $tenantContext) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        try {
            if ($user->isPlatformAdmin()) {
                return $next($request);
            }

            $tenant = $user->tenant;

            if ($tenant === null || $tenant->status === TenantStatus::Suspended) {
                abort(403);
            }

            $this->tenantContext->set($tenant);

            return $next($request);
        } finally {
            $this->tenantContext->forget();
        }
    }
}
