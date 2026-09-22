<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use DA\Admin\Services\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPermissionGates();
        $this->registerRateLimiters();
    }

    private function registerPermissionGates(): void
    {
        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, function (mixed $user) use ($permission): bool {
                return $user instanceof User && $user->hasPermission($permission);
            });
        }
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('api-login', function (Request $request) {
            return Limit::perMinute(5)->by(Str::transliterate(
                Str::lower($request->string('email')->toString()).'|'.$request->ip()
            ));
        });
    }
}
