<?php

use App\Enums\Permission;
use DA\Inventory\Http\Controllers\Api\V1\AttributeController;
use DA\Inventory\Http\Controllers\Api\V1\AttributeValueController;
use DA\Inventory\Http\Controllers\Api\V1\BrandController;
use DA\Inventory\Http\Controllers\Api\V1\CategoryController;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetAttributesDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetAttributeValuesDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetBrandsDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetCategoriesDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetStoresDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetSuppliersDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetUnitsDropdown;
use DA\Inventory\Http\Controllers\Api\V1\InventoryController;
use DA\Inventory\Http\Controllers\Api\V1\InventoryLayerController;
use DA\Inventory\Http\Controllers\Api\V1\ProductController;
use DA\Inventory\Http\Controllers\Api\V1\PurchaseController;
use DA\Inventory\Http\Controllers\Api\V1\StockAdjustmentController;
use DA\Inventory\Http\Controllers\Api\V1\StockMovementController;
use DA\Inventory\Http\Controllers\Api\V1\StockTransferController;
use DA\Inventory\Http\Controllers\Api\V1\StoreController;
use DA\Inventory\Http\Controllers\Api\V1\SupplierController;
use DA\Inventory\Http\Controllers\Api\V1\UnitController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum'])->group(function (): void {
    Route::get('categories-dropdown', GetCategoriesDropdown::class)
        ->middleware('permission:'.Permission::CategoriesView->value)
        ->name('categories.dropdown');
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

    Route::get('brands-dropdown', GetBrandsDropdown::class)
        ->middleware('permission:brands.view')
        ->name('brands.dropdown');
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

    Route::get('units-dropdown', GetUnitsDropdown::class)
        ->middleware('permission:units.view')
        ->name('units.dropdown');
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

    Route::get('attributes-dropdown', GetAttributesDropdown::class)
        ->middleware('permission:attributes.view')
        ->name('attributes.dropdown');
    Route::get('attribute-values-dropdown', GetAttributeValuesDropdown::class)
        ->middleware('permission:attributes.view')
        ->name('attributes.values.dropdown');
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
    Route::post('attributes/delete', [AttributeController::class, 'destroy'])
        ->middleware('permission:attributes.delete')
        ->name('attributes.destroy');

    Route::get('attributes-values', [AttributeValueController::class, 'index'])
        ->middleware('permission:attributes.view')
        ->name('attributes.values.index');
    Route::post('attributes-values', [AttributeValueController::class, 'store'])
        ->middleware('permission:attributes.create')
        ->name('attributes.values.store');
    Route::post('attributes-values/delete', [AttributeValueController::class, 'destroy'])
        ->middleware('permission:attributes.delete')
        ->name('attributes.values.destroy');
    Route::get('attributes-values/{valueId}', [AttributeValueController::class, 'show'])
        ->middleware('permission:attributes.view')
        ->name('attributes.values.show');
    Route::put('attributes-values/{valueId}', [AttributeValueController::class, 'update'])
        ->middleware('permission:attributes.update')
        ->name('attributes.values.update');

    Route::get('products/generate-sku', [ProductController::class, 'generateSku'])
        ->name('products.generate-sku');
    Route::get('products/generate-barcode', [ProductController::class, 'generateBarcode'])
        ->name('products.generate-barcode');
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

    Route::get('suppliers-dropdown', GetSuppliersDropdown::class)
        ->middleware('permission:suppliers.view')
        ->name('suppliers.dropdown');
    Route::get('suppliers', [SupplierController::class, 'index'])
        ->middleware('permission:suppliers.view')
        ->name('suppliers.index');
    Route::post('suppliers', [SupplierController::class, 'store'])
        ->middleware('permission:suppliers.create')
        ->name('suppliers.store');
    Route::post('suppliers/delete', [SupplierController::class, 'destroy'])
        ->middleware('permission:suppliers.delete')
        ->name('suppliers.destroy');
    Route::get('suppliers/{id}', [SupplierController::class, 'show'])
        ->middleware('permission:suppliers.view')
        ->name('suppliers.show');
    Route::put('suppliers/{id}', [SupplierController::class, 'update'])
        ->middleware('permission:suppliers.update')
        ->name('suppliers.update');

    Route::get('stores-dropdown', GetStoresDropdown::class)
        ->middleware('permission:stores.view')
        ->name('stores.dropdown');
    Route::get('stores', [StoreController::class, 'index'])
        ->middleware('permission:stores.view')
        ->name('stores.index');
    Route::post('stores', [StoreController::class, 'store'])
        ->middleware('permission:stores.create')
        ->name('stores.store');
    Route::post('stores/delete', [StoreController::class, 'destroy'])
        ->middleware('permission:stores.delete')
        ->name('stores.destroy');
    Route::get('stores/{id}', [StoreController::class, 'show'])
        ->middleware('permission:stores.view')
        ->name('stores.show');
    Route::put('stores/{id}', [StoreController::class, 'update'])
        ->middleware('permission:stores.update')
        ->name('stores.update');

    Route::get('purchases', [PurchaseController::class, 'index'])
        ->middleware('permission:purchasing.view')
        ->name('purchases.index');
    Route::post('purchases', [PurchaseController::class, 'store'])
        ->middleware('permission:purchasing.create')
        ->name('purchases.store');
    Route::post('purchases/delete', [PurchaseController::class, 'destroy'])
        ->middleware('permission:purchasing.delete')
        ->name('purchases.destroy');
    Route::put('purchases/{id}/status', [PurchaseController::class, 'updateStatus'])
        ->middleware('permission:purchasing.update')
        ->name('purchases.status');
    Route::get('purchases/{id}', [PurchaseController::class, 'show'])
        ->middleware('permission:purchasing.view')
        ->name('purchases.show');
    Route::put('purchases/{id}', [PurchaseController::class, 'update'])
        ->middleware('permission:purchasing.update')
        ->name('purchases.update');

    Route::get('inventory', [InventoryController::class, 'index'])
        ->middleware('permission:inventory.view')
        ->name('inventory.index');
    Route::get('inventory-movements', [StockMovementController::class, 'index'])
        ->middleware('permission:inventory.view')
        ->name('inventory.movements');
    Route::get('inventory-layers', [InventoryLayerController::class, 'index'])
        ->middleware('permission:inventory.view')
        ->name('inventory.layers');

    Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])
        ->middleware('permission:inventory.view')
        ->name('stock-adjustments.index');
    Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])
        ->middleware('permission:inventory.create')
        ->name('stock-adjustments.store');
    Route::put('stock-adjustments/{id}/status', [StockAdjustmentController::class, 'updateStatus'])
        ->middleware('permission:inventory.update')
        ->name('stock-adjustments.status');
    Route::get('stock-adjustments/{id}', [StockAdjustmentController::class, 'show'])
        ->middleware('permission:inventory.view')
        ->name('stock-adjustments.show');
    Route::put('stock-adjustments/{id}', [StockAdjustmentController::class, 'update'])
        ->middleware('permission:inventory.update')
        ->name('stock-adjustments.update');

    Route::get('stock-transfers', [StockTransferController::class, 'index'])
        ->middleware('permission:inventory.view')
        ->name('stock-transfers.index');
    Route::post('stock-transfers', [StockTransferController::class, 'store'])
        ->middleware('permission:inventory.create')
        ->name('stock-transfers.store');
    Route::put('stock-transfers/{id}/status', [StockTransferController::class, 'updateStatus'])
        ->middleware('permission:inventory.update')
        ->name('stock-transfers.status');
    Route::get('stock-transfers/{id}', [StockTransferController::class, 'show'])
        ->middleware('permission:inventory.view')
        ->name('stock-transfers.show');
    Route::put('stock-transfers/{id}', [StockTransferController::class, 'update'])
        ->middleware('permission:inventory.update')
        ->name('stock-transfers.update');
});
