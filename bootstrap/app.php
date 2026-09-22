<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureTenantUser;
use DA\Admin\Http\Middleware\EnsureAdminPermission;
use DA\Admin\Http\Middleware\EnsurePlatformAdmin;
use DA\Admin\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo('/admin');

        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'tenant.user' => EnsureTenantUser::class,
            'permission' => EnsurePermission::class,
            'platform.admin' => EnsurePlatformAdmin::class,
            'admin.permission' => EnsureAdminPermission::class,
        ]);

        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            ResolveTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
