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
        Route::put('my/info/update', [AuthController::class, 'updateInfo'])->name('self.update');
        Route::put('my/password/update', [AuthController::class, 'updatePassword'])->name('self.password.update');

        Route::get('permissions', [PermissionController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('permissions.index');

        Route::get('users', [UserController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('users.index');
        Route::post('users', [UserController::class, 'store'])
            ->middleware('permission:users.create')
            ->name('users.store');
        Route::get('users/{id}', [UserController::class, 'show'])
            ->middleware('permission:users.view')
            ->name('users.show');
        Route::put('users/{id}', [UserController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('users.update');
        Route::delete('users/{id}', [UserController::class, 'destroy'])
            ->middleware('permission:users.delete')
            ->name('users.destroy');
        Route::put('users/{id}/password', [UserPasswordController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('users.password.update');
        Route::post('users/{id}/photo', [UserPhotoController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('users.photo.update');
        Route::put('users/{id}/role', [AssignedUserRoleController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('users.roles.update');

        Route::get('roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])
            ->middleware('permission:roles.create')
            ->name('roles.store');
        Route::get('roles/{id}', [RoleController::class, 'show'])
            ->middleware('permission:roles.view')
            ->name('roles.show');
        Route::put('roles/{id}', [RoleController::class, 'update'])
            ->middleware('permission:roles.update')
            ->name('roles.update');
        Route::delete('roles/{id}', [RoleController::class, 'destroy'])
            ->middleware('permission:roles.delete')
            ->name('roles.destroy');
    });
});
