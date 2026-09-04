<?php

use DA\Admin\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest:admin')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('api/login', [AuthenticatedSessionController::class, 'create']);
    Route::post('api/login', [AuthenticatedSessionController::class, 'store']);
});
