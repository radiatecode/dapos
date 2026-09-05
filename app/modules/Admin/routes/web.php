<?php

use DA\Admin\Http\Controllers\AddonController;
use DA\Admin\Http\Controllers\Auth\AuthenticatedSessionController;
use DA\Admin\Http\Controllers\DashboardController;
use DA\Admin\Http\Controllers\FeatureController;
use DA\Admin\Http\Controllers\PermissionController;
use DA\Admin\Http\Controllers\PlanController;
use DA\Admin\Http\Controllers\ProfileController;
use DA\Admin\Http\Controllers\SubscriptionController;
use DA\Admin\Http\Controllers\SubscriptionEventController;
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

        Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        Route::resource('tenants', TenantController::class);

        Route::patch('plans/{plan}/activate', [PlanController::class, 'activate'])->name('plans.activate');
        Route::patch('plans/{plan}/deactivate', [PlanController::class, 'deactivate'])->name('plans.deactivate');
        Route::resource('plans', PlanController::class)->except(['destroy']);

        Route::get('features', [FeatureController::class, 'index'])->name('features.index');
        Route::post('features', [FeatureController::class, 'store'])->name('features.store');
        Route::put('features/{feature}', [FeatureController::class, 'update'])->name('features.update');

        Route::get('addons', [AddonController::class, 'index'])->name('addons.index');
        Route::post('addons', [AddonController::class, 'store'])->name('addons.store');
        Route::put('addons/{addon}', [AddonController::class, 'update'])->name('addons.update');

        Route::get('subscriptions/create', [SubscriptionController::class, 'create'])
            ->middleware('admin.permission:subscriptions.create')
            ->name('subscriptions.create');
        Route::post('subscriptions', [SubscriptionController::class, 'store'])
            ->middleware('admin.permission:subscriptions.create')
            ->name('subscriptions.store');

        Route::middleware('admin.permission:subscriptions.view')->group(function () {
            Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
            Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
        });

        Route::middleware('admin.permission:subscriptions.manage')->group(function () {
            Route::patch('subscriptions/{subscription}/start-trial', [SubscriptionController::class, 'startTrial'])->name('subscriptions.start-trial');
            Route::patch('subscriptions/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('subscriptions.activate');
            Route::patch('subscriptions/{subscription}/pause', [SubscriptionController::class, 'pause'])->name('subscriptions.pause');
            Route::patch('subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('subscriptions.resume');
            Route::patch('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
            Route::patch('subscriptions/{subscription}/expire', [SubscriptionController::class, 'expire'])->name('subscriptions.expire');
            Route::patch('subscriptions/{subscription}/past-due', [SubscriptionController::class, 'markPastDue'])->name('subscriptions.past-due');
            Route::post('subscriptions/{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
            Route::patch('subscriptions/{subscription}/grace-days', [SubscriptionController::class, 'updateGraceDays'])->name('subscriptions.grace-days');
            Route::post('subscriptions/{subscription}/addons', [SubscriptionController::class, 'addAddon'])->name('subscriptions.addons.store');
            Route::patch('subscriptions/{subscription}/addons/{addon}', [SubscriptionController::class, 'updateAddonQuantity'])->name('subscriptions.addons.update');
            Route::delete('subscriptions/{subscription}/addons/{addon}', [SubscriptionController::class, 'removeAddon'])->name('subscriptions.addons.destroy');
        });

        Route::get('subscription-events', [SubscriptionEventController::class, 'index'])
            ->middleware('admin.permission:subscription-events.view')
            ->name('subscription-events.index');
    });
});
