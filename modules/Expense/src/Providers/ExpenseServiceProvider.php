<?php

namespace DA\Expense\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ExpenseServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerRoutes();

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'expense');
    }

    private function registerRoutes(): void
    {
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
    private function apiRouteConfiguration(): array
    {
        return [
            'prefix' => 'api',
            'middleware' => ['api'],
        ];
    }
}
