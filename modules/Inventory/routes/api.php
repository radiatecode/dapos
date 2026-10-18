<?php

use App\Enums\Permission;
use DA\Inventory\Http\Controllers\Api\V1\AttributeController;
use DA\Inventory\Http\Controllers\Api\V1\AttributeValueController;
use DA\Inventory\Http\Controllers\Api\V1\BarcodeLabelController;
use DA\Inventory\Http\Controllers\Api\V1\BrandController;
use DA\Inventory\Http\Controllers\Api\V1\CategoryController;
use DA\Inventory\Http\Controllers\Api\V1\CurrencyController;
use DA\Inventory\Http\Controllers\Api\V1\CurrencySettingController;
use DA\Inventory\Http\Controllers\Api\V1\CurrentStockValueReportController;
use DA\Inventory\Http\Controllers\Api\V1\CustomerController;
use DA\Inventory\Http\Controllers\Api\V1\DashboardController;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetAttributesDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetAttributeValuesDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetBrandsDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetCategoriesDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetCustomersDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetStoresDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetSuppliersDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetTaxesDropdown;
use DA\Inventory\Http\Controllers\Api\V1\Dropdown\GetUnitsDropdown;
use DA\Inventory\Http\Controllers\Api\V1\InventoryController;
use DA\Inventory\Http\Controllers\Api\V1\InventoryLayerController;
use DA\Inventory\Http\Controllers\Api\V1\MinStockReportController;
use DA\Inventory\Http\Controllers\Api\V1\ProductBatchController;
use DA\Inventory\Http\Controllers\Api\V1\ProductController;
use DA\Inventory\Http\Controllers\Api\V1\ProfitReportController;
use DA\Inventory\Http\Controllers\Api\V1\PurchaseController;
use DA\Inventory\Http\Controllers\Api\V1\PurchaseReturnController;
use DA\Inventory\Http\Controllers\Api\V1\QuickSaleController;
use DA\Inventory\Http\Controllers\Api\V1\QuickSalesReportController;
use DA\Inventory\Http\Controllers\Api\V1\SaleController;
use DA\Inventory\Http\Controllers\Api\V1\SalesInvoiceReportController;
use DA\Inventory\Http\Controllers\Api\V1\SalesReportController;
use DA\Inventory\Http\Controllers\Api\V1\StockAdjustmentController;
use DA\Inventory\Http\Controllers\Api\V1\StockMovementController;
use DA\Inventory\Http\Controllers\Api\V1\StockTransferController;
use DA\Inventory\Http\Controllers\Api\V1\StoreController;
use DA\Inventory\Http\Controllers\Api\V1\SupplierController;
use DA\Inventory\Http\Controllers\Api\V1\TaxController;
use DA\Inventory\Http\Controllers\Api\V1\TaxSettingController;
use DA\Inventory\Http\Controllers\Api\V1\TopSalesReportController;
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
    Route::post('categories/delete', [CategoryController::class, 'destroy'])
        ->middleware('permission:'.Permission::CategoriesDelete->value)
        ->name('categories.destroy');
    Route::get('categories/{category}', [CategoryController::class, 'show'])
        ->name('categories.show');
    Route::post('categories/{category}', [CategoryController::class, 'update'])
        ->name('categories.update');

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

    Route::get('products/batch/sample', [ProductBatchController::class, 'sample'])
        ->middleware('permission:products.create')
        ->name('products.batch.sample');
    Route::post('products/batch/verify', [ProductBatchController::class, 'verify'])
        ->middleware('permission:products.create')
        ->name('products.batch.verify');
    Route::post('products/batch', [ProductBatchController::class, 'store'])
        ->middleware('permission:products.create')
        ->name('products.batch.store');

    Route::get('products/summary', [ProductController::class, 'summary'])
        ->middleware('permission:products.view')
        ->name('products.summary');
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

    Route::get('barcode-labels/variants', [BarcodeLabelController::class, 'index'])
        ->middleware('permission:'.Permission::BarcodesPrint->value)
        ->name('barcode-labels.variants');

    Route::get('customers-dropdown', GetCustomersDropdown::class)
        ->middleware('permission:'.Permission::CustomersView->value)
        ->name('customers.dropdown');
    Route::get('customers', [CustomerController::class, 'index'])
        ->middleware('permission:'.Permission::CustomersView->value)
        ->name('customers.index');
    Route::post('customers', [CustomerController::class, 'store'])
        ->middleware('permission:'.Permission::CustomersCreate->value)
        ->name('customers.store');
    Route::post('customers/delete', [CustomerController::class, 'destroy'])
        ->middleware('permission:'.Permission::CustomersDelete->value)
        ->name('customers.destroy');
    Route::get('customers/{id}', [CustomerController::class, 'show'])
        ->middleware('permission:'.Permission::CustomersView->value)
        ->name('customers.show');
    Route::put('customers/{id}', [CustomerController::class, 'update'])
        ->middleware('permission:'.Permission::CustomersUpdate->value)
        ->name('customers.update');

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
    Route::get('purchases/summary', [PurchaseController::class, 'summary'])
        ->middleware('permission:purchasing.view')
        ->name('purchases.summary');
    Route::post('purchases', [PurchaseController::class, 'store'])
        ->middleware('permission:purchasing.create')
        ->name('purchases.store');
    Route::post('purchases/delete', [PurchaseController::class, 'destroy'])
        ->middleware('permission:purchasing.delete')
        ->name('purchases.destroy');
    Route::put('purchases/{id}/status', [PurchaseController::class, 'updateStatus'])
        ->middleware('permission:purchasing.update')
        ->name('purchases.status');
    Route::post('purchases/{id}/payments', [PurchaseController::class, 'storePayment'])
        ->middleware('permission:purchasing.update')
        ->name('purchases.payments.store');
    Route::get('purchases/{id}/pdf', [PurchaseController::class, 'pdf'])
        ->middleware('permission:purchasing.view')
        ->name('purchases.pdf');
    Route::get('purchases/{id}', [PurchaseController::class, 'show'])
        ->middleware('permission:purchasing.view')
        ->name('purchases.show');
    Route::put('purchases/{id}', [PurchaseController::class, 'update'])
        ->middleware('permission:purchasing.update')
        ->name('purchases.update');

    Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])
        ->middleware('permission:purchasing.view')
        ->name('purchase-returns.index');
    Route::post('purchase-returns', [PurchaseReturnController::class, 'store'])
        ->middleware('permission:purchasing.create')
        ->name('purchase-returns.store');
    Route::post('purchase-returns/delete', [PurchaseReturnController::class, 'destroy'])
        ->middleware('permission:purchasing.delete')
        ->name('purchase-returns.destroy');
    Route::get('purchase-returns/summary', [PurchaseReturnController::class, 'summary'])
        ->middleware('permission:purchasing.view')
        ->name('purchase-returns.summary');
    Route::get('purchase-returns/purchases/{id}/lines', [PurchaseReturnController::class, 'purchaseLines'])
        ->middleware('permission:purchasing.view')
        ->name('purchase-returns.purchase-lines');
    Route::put('purchase-returns/{id}/status', [PurchaseReturnController::class, 'updateStatus'])
        ->middleware('permission:purchasing.update')
        ->name('purchase-returns.status');
    Route::get('purchase-returns/{id}', [PurchaseReturnController::class, 'show'])
        ->middleware('permission:purchasing.view')
        ->name('purchase-returns.show');
    Route::put('purchase-returns/{id}', [PurchaseReturnController::class, 'update'])
        ->middleware('permission:purchasing.update')
        ->name('purchase-returns.update');

    Route::get('sales', [SaleController::class, 'index'])
        ->middleware('permission:'.Permission::SalesView->value)
        ->name('sales.index');
    Route::get('sales/summary', [SaleController::class, 'summary'])
        ->middleware('permission:'.Permission::SalesView->value)
        ->name('sales.summary');
    Route::post('sales', [SaleController::class, 'store'])
        ->middleware('permission:'.Permission::SalesCreate->value)
        ->name('sales.store');
    Route::post('sales/{id}/payments', [SaleController::class, 'storePayment'])
        ->middleware('permission:'.Permission::SalesCreate->value)
        ->name('sales.payments.store');
    Route::get('sales/{id}', [SaleController::class, 'show'])
        ->middleware('permission:'.Permission::SalesView->value)
        ->name('sales.show');

    Route::get('quick-sales', [QuickSaleController::class, 'index'])
        ->middleware('permission:'.Permission::QuickSalesView->value)
        ->name('quick-sales.index');
    Route::get('quick-sales/summary', [QuickSaleController::class, 'summary'])
        ->middleware('permission:'.Permission::QuickSalesView->value)
        ->name('quick-sales.summary');
    Route::post('quick-sales', [QuickSaleController::class, 'store'])
        ->middleware('permission:'.Permission::QuickSalesCreate->value)
        ->name('quick-sales.store');
    Route::get('quick-sales/{id}', [QuickSaleController::class, 'show'])
        ->whereNumber('id')
        ->middleware('permission:'.Permission::QuickSalesView->value)
        ->name('quick-sales.show');

    Route::prefix('dashboard')
        ->name('dashboard.')
        ->middleware('permission:'.Permission::DashboardView->value)
        ->group(function (): void {
            Route::get('summary', [DashboardController::class, 'summary'])->name('summary');
            Route::get('sales-trend', [DashboardController::class, 'salesTrend'])->name('sales-trend');
            Route::get('quick-sales-today', [DashboardController::class, 'quickSalesToday'])->name('quick-sales-today');
        });

    Route::prefix('reports/sales')
        ->name('reports.sales.')
        ->middleware('permission:'.Permission::SalesReportsView->value)
        ->group(function (): void {
            Route::get('/', [SalesReportController::class, 'index'])->name('index');
            Route::get('summary', [SalesReportController::class, 'summary'])->name('summary');
            Route::get('export/pdf', [SalesReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('export/excel', [SalesReportController::class, 'exportExcel'])->name('export.excel');
        });

    Route::prefix('reports/top-sales')
        ->name('reports.top-sales.')
        ->middleware('permission:'.Permission::TopSalesReportsView->value)
        ->group(function (): void {
            Route::get('/', [TopSalesReportController::class, 'index'])->name('index');
            Route::get('summary', [TopSalesReportController::class, 'summary'])->name('summary');
            Route::get('export/pdf', [TopSalesReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('export/excel', [TopSalesReportController::class, 'exportExcel'])->name('export.excel');
        });

    Route::prefix('reports/sales-invoices')
        ->name('reports.sales-invoices.')
        ->middleware('permission:'.Permission::SalesInvoicesReportsView->value)
        ->group(function (): void {
            Route::get('export/pdf', [SalesInvoiceReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('export/excel', [SalesInvoiceReportController::class, 'exportExcel'])->name('export.excel');
        });

    Route::prefix('reports/quick-sales')
        ->name('reports.quick-sales.')
        ->middleware('permission:'.Permission::QuickSalesReportsView->value)
        ->group(function (): void {
            Route::get('/', [QuickSalesReportController::class, 'index'])->name('index');
            Route::get('summary', [QuickSalesReportController::class, 'summary'])->name('summary');
            Route::get('export/pdf', [QuickSalesReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('export/excel', [QuickSalesReportController::class, 'exportExcel'])->name('export.excel');
        });

    Route::prefix('reports/profit')
        ->name('reports.profit.')
        ->middleware('permission:'.Permission::ProfitReportsView->value)
        ->group(function (): void {
            Route::get('/', [ProfitReportController::class, 'index'])->name('index');
            Route::get('summary', [ProfitReportController::class, 'summary'])->name('summary');
            Route::get('export/pdf', [ProfitReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('export/excel', [ProfitReportController::class, 'exportExcel'])->name('export.excel');
        });

    Route::prefix('reports/min-stock')
        ->name('reports.min-stock.')
        ->middleware('permission:'.Permission::MinStockReportsView->value)
        ->group(function (): void {
            Route::get('/', [MinStockReportController::class, 'index'])->name('index');
            Route::get('summary', [MinStockReportController::class, 'summary'])->name('summary');
            Route::get('export/pdf', [MinStockReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('export/excel', [MinStockReportController::class, 'exportExcel'])->name('export.excel');
        });

    Route::prefix('reports/current-stock-value')
        ->name('reports.current-stock-value.')
        ->middleware('permission:'.Permission::CurrentStockValueReportsView->value)
        ->group(function (): void {
            Route::get('/', [CurrentStockValueReportController::class, 'index'])->name('index');
            Route::get('summary', [CurrentStockValueReportController::class, 'summary'])->name('summary');
            Route::get('export/pdf', [CurrentStockValueReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('export/excel', [CurrentStockValueReportController::class, 'exportExcel'])->name('export.excel');
        });

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

    Route::get('taxes-dropdown', GetTaxesDropdown::class)
        ->middleware('permission:taxes.view')
        ->name('taxes.dropdown');

    Route::get('tax-settings', [TaxSettingController::class, 'show'])
        ->middleware('permission:taxes.view')
        ->name('tax-settings.show');
    Route::put('tax-settings', [TaxSettingController::class, 'update'])
        ->middleware('permission:taxes.update')
        ->name('tax-settings.update');

    Route::get('currencies', [CurrencyController::class, 'index'])
        ->middleware('permission:'.Permission::SettingsView->value)
        ->name('currencies.index');

    Route::get('currency-settings', [CurrencySettingController::class, 'show'])
        ->middleware('permission:'.Permission::SettingsView->value)
        ->name('currency-settings.show');
    Route::put('currency-settings', [CurrencySettingController::class, 'update'])
        ->middleware('permission:'.Permission::SettingsUpdate->value)
        ->name('currency-settings.update');

    Route::get('taxes', [TaxController::class, 'index'])
        ->middleware('permission:taxes.view')
        ->name('taxes.index');
    Route::post('taxes', [TaxController::class, 'store'])
        ->middleware('permission:taxes.create')
        ->name('taxes.store');
    Route::put('taxes/{id}', [TaxController::class, 'update'])
        ->middleware('permission:taxes.update')
        ->name('taxes.update');
    Route::get('taxes/{id}', [TaxController::class, 'show'])
        ->middleware('permission:taxes.view')
        ->name('taxes.show');
    Route::post('taxes/delete', [TaxController::class, 'destroy'])
        ->middleware('permission:taxes.delete')
        ->name('taxes.destroy');
});
