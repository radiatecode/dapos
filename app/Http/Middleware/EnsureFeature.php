<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use DA\Admin\Exceptions\FeatureUnavailableException;
use DA\Admin\Services\FeatureService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeature
{
    public function __construct(private FeatureService $features) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isActive()) {
            abort(403, 'This account is inactive.');
        }

        $user->loadMissing('tenant', 'tenant.subscription');

        if ($user->tenant === null || ! $user->tenant->isActive()) {
            abort(403, 'This business account is not active.');
        }

        if ($user->tenant->subscription === null) {
            abort(403, 'This business account is not subscribed.');
        }

        if (! $this->features->hasFeature($user->tenant->subscription, $feature)) {
            throw new FeatureUnavailableException('This feature is not available on the current subscription.');
        }

        return $next($request);
    }
}
