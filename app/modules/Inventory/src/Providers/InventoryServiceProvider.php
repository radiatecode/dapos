<?php

namespace DA\Inventory\Providers;

use DA\Inventory\Models\Category;
use DA\Inventory\Policies\CategoryPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerRoutes();

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    private function registerPolicies(): void
    {
        Gate::policy(Category::class, CategoryPolicy::class);
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
