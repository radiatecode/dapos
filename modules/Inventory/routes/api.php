<?php

use App\Enums\Permission;
use DA\Inventory\Http\Controllers\Api\V1\AttributeController;
use DA\Inventory\Http\Controllers\Api\V1\AttributeValueController;
use DA\Inventory\Http\Controllers\Api\V1\BrandController;
use DA\Inventory\Http\Controllers\Api\V1\CategoryController;
use DA\Inventory\Http\Controllers\Api\V1\ProductController;
use DA\Inventory\Http\Controllers\Api\V1\UnitController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum'])->group(function (): void {
    Route::get('categories', [CategoryController::class, 'index'])
        ->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])
        ->name('categories.store');
    Route::get('categories/{category}', [CategoryController::class, 'show'])
        ->name('categories.show');
    Route::post('categories/{category}', [CategoryController::class, 'update'])
        ->name('categories.update');
    Route::post('categories/delete', [CategoryController::class, 'destroy'])
        ->middleware('permission:'.Permission::CategoriesDelete->value)
        ->name('categories.destroy');

    Route::get('brands', [BrandController::class, 'index'])
        ->middleware('permission:brands.view')
        ->name('brands.index');
    Route::post('brands', [BrandController::class, 'store'])
        ->middleware('permission:brands.create')
        ->name('brands.store');
    Route::post('brands/delete', [BrandController::class, 'destroy'])
        ->middleware('permission:brands.delete')
        ->name('brands.destroy');
    Route::get('brands/{id}', [BrandController::class, 'show'])
        ->middleware('permission:brands.view')
        ->name('brands.show');
    Route::post('brands/{id}', [BrandController::class, 'update'])
        ->middleware('permission:brands.update')
        ->name('brands.update');

    Route::get('units', [UnitController::class, 'index'])
        ->middleware('permission:units.view')
        ->name('units.index');
    Route::post('units', [UnitController::class, 'store'])
        ->middleware('permission:units.create')
        ->name('units.store');
    Route::get('units/{id}', [UnitController::class, 'show'])
        ->middleware('permission:units.view')
        ->name('units.show');
    Route::put('units/{id}', [UnitController::class, 'update'])
        ->middleware('permission:units.update')
        ->name('units.update');
    Route::post('units/delete', [UnitController::class, 'destroy'])
        ->middleware('permission:units.delete')
        ->name('units.destroy');

    Route::get('attributes', [AttributeController::class, 'index'])
        ->middleware('permission:attributes.view')
        ->name('attributes.index');
    Route::post('attributes', [AttributeController::class, 'store'])
        ->middleware('permission:attributes.create')
        ->name('attributes.store');
    Route::get('attributes/{id}', [AttributeController::class, 'show'])
        ->middleware('permission:attributes.view')
        ->name('attributes.show');
    Route::put('attributes/{id}', [AttributeController::class, 'update'])
        ->middleware('permission:attributes.update')
        ->name('attributes.update');
    Route::delete('attributes/{id}', [AttributeController::class, 'destroy'])
        ->middleware('permission:attributes.delete')
        ->name('attributes.destroy');

    Route::get('attributes/{id}/values', [AttributeValueController::class, 'index'])
        ->middleware('permission:attributes.view')
        ->name('attributes.values.index');
    Route::post('attributes/{id}/values', [AttributeValueController::class, 'store'])
        ->middleware('permission:attributes.create')
        ->name('attributes.values.store');
    Route::get('attributes/{id}/values/{valueId}', [AttributeValueController::class, 'show'])
        ->middleware('permission:attributes.view')
        ->name('attributes.values.show');
    Route::put('attributes/{id}/values/{valueId}', [AttributeValueController::class, 'update'])
        ->middleware('permission:attributes.update')
        ->name('attributes.values.update');
    Route::delete('attributes/{id}/values/{valueId}', [AttributeValueController::class, 'destroy'])
        ->middleware('permission:attributes.delete')
        ->name('attributes.values.destroy');

    Route::get('products', [ProductController::class, 'index'])
        ->middleware('permission:products.view')
        ->name('products.index');
    Route::post('products', [ProductController::class, 'store'])
        ->middleware('permission:products.create')
        ->name('products.store');
    Route::get('products/{id}', [ProductController::class, 'show'])
        ->middleware('permission:products.view')
        ->name('products.show');
    Route::put('products/{id}', [ProductController::class, 'update'])
        ->middleware('permission:products.update')
        ->name('products.update');
});
