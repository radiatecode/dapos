<?php

namespace DA\Admin\Providers;

use DA\Admin\Console\Commands\ProcessSubscriptionPeriods;
use DA\Admin\Enums\AdminPermission;
use DA\Admin\Models\AdminUser;
use DA\Admin\Services\Billing\ManualPaymentProcessor;
use DA\Admin\Services\Billing\PaymentProcessor;
use DA\Admin\Services\Entitlements\ResourceUsageRegistry;
use DA\Admin\Services\Entitlements\UsersResourceCounter;
use DA\Admin\Support\AdminNavigation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdminNavigation::class);

        $this->app->singleton(ResourceUsageRegistry::class, function (): ResourceUsageRegistry {
            return new ResourceUsageRegistry([
                new UsersResourceCounter,
            ]);
        });

        $this->app->bind(PaymentProcessor::class, ManualPaymentProcessor::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerGates();
        $this->publishAdminStyles();

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessSubscriptionPeriods::class,
            ]);
        }
    }

    private function publishAdminStyles(): void
    {
        $source = __DIR__.'/../../resources/css/style.css';
        $destination = public_path('vendor/admin/style.css');

        if (! File::isFile($source)) {
            return;
        }

        if (File::isFile($destination) && File::lastModified($destination) >= File::lastModified($source)) {
            return;
        }

        try {
            File::ensureDirectoryExists(dirname($destination));
            File::copy($source, $destination);
        } catch (\Throwable) {
            // public/ may be read-only in some environments
        }
    }

    private function registerViews(): void
    {
        $views = __DIR__.'/../../resources/views';

        $this->loadViewsFrom($views, 'admin');

        Blade::anonymousComponentPath($views.'/components', 'admin');

        View::composer('admin::app.partials._left_nav', function ($view): void {
            $admin = auth('admin')->user();

            $view->with(
                'navigation',
                $admin instanceof AdminUser ? $this->app->make(AdminNavigation::class)->visibleFor($admin) : [],
            );
        });
    }

    private function registerGates(): void
    {
        foreach (AdminPermission::cases() as $permission) {
            Gate::define($permission->value, function ($user) use ($permission): bool {
                return $user instanceof AdminUser && $user->hasAdminPermission($permission);
            });
        }
    }

    private function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        });

        $apiRoutes = __DIR__.'/../../routes/api.php';

        if (is_file($apiRoutes)) {
            Route::group($this->apiRouteConfiguration(), function () use ($apiRoutes) {
                $this->loadRoutesFrom($apiRoutes);
            });
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function routeConfiguration(): array
    {
        return [
            'middleware' => ['web'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function apiRouteConfiguration(): array
    {
        return [
            'prefix' => 'api',
            'middleware' => ['api'],
        ];
    }
}
