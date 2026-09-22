<?php

use App\Http\Controllers\Api\V1\AssignedUserRoleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserPasswordController;
use App\Http\Controllers\Api\V1\UserPhotoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:api-login')
        ->name('login');

    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::get('permissions', [PermissionController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('permissions.index');

        Route::get('users', [UserController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('users.index');
        Route::post('users', [UserController::class, 'store'])
            ->middleware('permission:users.create')
            ->name('users.store');
        Route::get('users/{user}', [UserController::class, 'show'])
            ->middleware('permission:users.view')
            ->name('users.show');
        Route::put('users/{user}', [UserController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])
            ->middleware('permission:users.delete')
            ->name('users.destroy');
        Route::put('users/{user}/password', [UserPasswordController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('users.password.update');
        Route::post('users/{user}/photo', [UserPhotoController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('users.photo.update');
        Route::put('users/{user}/roles', [AssignedUserRoleController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('users.roles.update');

        Route::get('roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])
            ->middleware('permission:roles.create')
            ->name('roles.store');
        Route::get('roles/{role}', [RoleController::class, 'show'])
            ->middleware('permission:roles.view')
            ->name('roles.show');
        Route::put('roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:roles.update')
            ->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:roles.delete')
            ->name('roles.destroy');
    });
});
