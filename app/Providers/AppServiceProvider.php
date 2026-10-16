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
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPermissionGates();
        $this->registerRateLimiters();

        LogViewer::auth(function ($request) {
            return $request->user('admin')
                && $request->user('admin')->email === 'test@example.com';
        });
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
