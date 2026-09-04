<?php

use DA\Admin\Http\Controllers\Auth\AuthenticatedSessionController;
use DA\Admin\Http\Controllers\DashboardController;
use DA\Admin\Http\Controllers\PermissionController;
use DA\Admin\Http\Controllers\ProfileController;
use DA\Admin\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth:admin', 'platform.admin'])->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('profile/information', [ProfileController::class, 'update'])->name('profile.information');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::get('permissions', [PermissionController::class, 'index'])
            ->middleware('admin.permission:permissions.view')
            ->name('permissions');

        Route::resource('tenants', TenantController::class)->except(['show']);
    });
});
