<?php

namespace App\Enums;

enum Permission: string
{
    case DashboardView = 'dashboard.view';

    case ProductsView = 'products.view';
    case ProductsCreate = 'products.create';
    case ProductsUpdate = 'products.update';
    case ProductsDelete = 'products.delete';

    case BarcodesPrint = 'barcodes.print';

    case CategoriesView = 'categories.view';
    case CategoriesCreate = 'categories.create';
    case CategoriesUpdate = 'categories.update';
    case CategoriesDelete = 'categories.delete';

    case BrandsView = 'brands.view';
    case BrandsCreate = 'brands.create';
    case BrandsUpdate = 'brands.update';
    case BrandsDelete = 'brands.delete';

    case UnitsView = 'units.view';
    case UnitsCreate = 'units.create';
    case UnitsUpdate = 'units.update';
    case UnitsDelete = 'units.delete';

    case AttributesView = 'attributes.view';
    case AttributesCreate = 'attributes.create';
    case AttributesUpdate = 'attributes.update';
    case AttributesDelete = 'attributes.delete';

    case CustomersView = 'customers.view';
    case CustomersCreate = 'customers.create';
    case CustomersUpdate = 'customers.update';
    case CustomersDelete = 'customers.delete';

    case SuppliersView = 'suppliers.view';
    case SuppliersCreate = 'suppliers.create';
    case SuppliersUpdate = 'suppliers.update';
    case SuppliersDelete = 'suppliers.delete';

    case LocationsView = 'locations.view';
    case LocationsCreate = 'locations.create';
    case LocationsUpdate = 'locations.update';
    case LocationsDelete = 'locations.delete';

    case StoresView = 'stores.view';
    case StoresCreate = 'stores.create';
    case StoresUpdate = 'stores.update';
    case StoresDelete = 'stores.delete';

    case PurchasingView = 'purchasing.view';
    case PurchasingCreate = 'purchasing.create';
    case PurchasingUpdate = 'purchasing.update';
    case PurchasingDelete = 'purchasing.delete';

    case InventoryView = 'inventory.view';
    case InventoryMovementsView = 'inventory.movements.view';
    case InventoryLayersView = 'inventory.layers.view';

    case InventoryAdjustmentView = 'inventory.adjustments.view';
    case InventoryAdjustmentCreate = 'inventory.adjustments.create';
    case InventoryAdjustmentUpdate = 'inventory.adjustments.update';
    case InventoryAdjustmentDelete = 'inventory.adjustments.delete';

    case InventoryTransferView = 'inventory.transfers.view';
    case InventoryTransferCreate = 'inventory.transfers.create';
    case InventoryTransferUpdate = 'inventory.transfers.update';
    case InventoryTransferDelete = 'inventory.transfers.delete';

    case InventoryUpdate = 'inventory.update';
    case InventoryDelete = 'inventory.delete';

    case SalesView = 'sales.view';
    case SalesQuantityView = 'sales.quantity.view';
    case SalesCreate = 'sales.create';
    case SalesUpdate = 'sales.update';
    case SalesDelete = 'sales.delete';

    case QuickSalesView = 'quick-sales.view';
    case QuickSalesCreate = 'quick-sales.create';

    case ReturnsView = 'returns.view';
    case ReturnsCreate = 'returns.create';
    case ReturnsUpdate = 'returns.update';
    case ReturnsDelete = 'returns.delete';

    case CashRegisterView = 'cash-register.view';
    case CashRegisterManage = 'cash-register.manage';

    case SalesReportsView = 'sales.reports.view';
    case SalesQuantityReportsView = 'sales.quantity.reports.view';
    case TopSalesReportsView = 'top-sales.reports.view';
    case SalesInvoicesReportsView = 'sales-invoices.reports.view';
    case ProfitReportsView = 'profit.reports.view';
    case MinStockReportsView = 'min-stock.reports.view';
    case CurrentStockValueReportsView = 'current-stock-value.reports.view';
    case QuickSalesReportsView = 'quick-sales.reports.view';

    case SettingsView = 'settings.view';
    case SettingsUpdate = 'settings.update';

    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';

    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesDelete = 'roles.delete';

    case TaxesView = 'taxes.view';
    case TaxesCreate = 'taxes.create';
    case TaxesUpdate = 'taxes.update';
    case TaxesDelete = 'taxes.delete';

    case OnlyCreatorUpdate = 'only-creator.update';
    case OnlyCreatorDelete = 'only-creator.delete';
    case OnlyTodayData = 'only-today.data';

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'View dashboard',
            self::ProductsView => 'View products',
            self::ProductsCreate => 'Create products',
            self::ProductsUpdate => 'Update products',
            self::ProductsDelete => 'Delete products',
            self::BarcodesPrint => 'Print barcode labels',
            self::CategoriesView => 'View categories',
            self::CategoriesCreate => 'Create categories',
            self::CategoriesUpdate => 'Update categories',
            self::CategoriesDelete => 'Delete categories',
            self::BrandsView => 'View brands',
            self::BrandsCreate => 'Create brands',
            self::BrandsUpdate => 'Update brands',
            self::BrandsDelete => 'Delete brands',
            self::UnitsView => 'View units',
            self::UnitsCreate => 'Create units',
            self::UnitsUpdate => 'Update units',
            self::UnitsDelete => 'Delete units',
            self::AttributesView => 'View attributes',
            self::AttributesCreate => 'Create attributes',
            self::AttributesUpdate => 'Update attributes',
            self::AttributesDelete => 'Delete attributes',
            self::CustomersView => 'View customers',
            self::CustomersCreate => 'Create customers',
            self::CustomersUpdate => 'Update customers',
            self::CustomersDelete => 'Delete customers',
            self::SuppliersView => 'View suppliers',
            self::SuppliersCreate => 'Create suppliers',
            self::SuppliersUpdate => 'Update suppliers',
            self::SuppliersDelete => 'Delete suppliers',
            self::LocationsView => 'View locations',
            self::LocationsCreate => 'Create locations',
            self::LocationsUpdate => 'Update locations',
            self::LocationsDelete => 'Delete locations',
            self::StoresView => 'View stores',
            self::StoresCreate => 'Create stores',
            self::StoresUpdate => 'Update stores',
            self::StoresDelete => 'Delete stores',
            self::PurchasingView => 'View purchasing',
            self::PurchasingCreate => 'Create purchases',
            self::PurchasingUpdate => 'Update purchases',
            self::PurchasingDelete => 'Delete purchases',
            self::InventoryView => 'View inventory',
            self::InventoryMovementsView => 'View inventory movements',
            self::InventoryLayersView => 'View inventory layers',
            self::InventoryAdjustmentView => 'View inventory adjustments',
            self::InventoryAdjustmentCreate => 'Create inventory adjustments',
            self::InventoryAdjustmentUpdate => 'Update inventory adjustments',
            self::InventoryAdjustmentDelete => 'Delete inventory adjustments',
            self::InventoryTransferView => 'View inventory transfers',
            self::InventoryTransferCreate => 'Create inventory transfers',
            self::InventoryTransferUpdate => 'Update inventory transfers',
            self::InventoryTransferDelete => 'Delete inventory transfers',
            self::SalesView => 'View sales',
            self::SalesCreate => 'Create sales',
            self::SalesUpdate => 'Update sales',
            self::SalesDelete => 'Delete sales',
            self::QuickSalesView => 'View quick sales',
            self::QuickSalesCreate => 'Create quick sales',
            self::ReturnsView => 'View returns',
            self::ReturnsCreate => 'Create returns',
            self::ReturnsUpdate => 'Update returns',
            self::ReturnsDelete => 'Delete returns',
            self::CashRegisterView => 'View cash register',
            self::CashRegisterManage => 'Manage cash register',
            self::SalesReportsView => 'View sales reports',
            self::TopSalesReportsView => 'View top sales reports',
            self::SalesInvoicesReportsView => 'View sales invoices reports',
            self::ProfitReportsView => 'View profit reports',
            self::MinStockReportsView => 'View min stock reports',
            self::CurrentStockValueReportsView => 'View current stock value reports',
            self::QuickSalesReportsView => 'View quick sales reports',
            self::SettingsView => 'View settings',
            self::SettingsUpdate => 'Update settings',
            self::UsersView => 'View users',
            self::UsersCreate => 'Create users',
            self::UsersUpdate => 'Update users',
            self::UsersDelete => 'Delete users',
            self::RolesView => 'View roles',
            self::RolesCreate => 'Create roles',
            self::RolesUpdate => 'Update roles',
            self::RolesDelete => 'Delete roles',
            self::TaxesView => 'View taxes',
            self::TaxesCreate => 'Create taxes',
            self::TaxesUpdate => 'Update taxes',
            self::TaxesDelete => 'Delete taxes',
            self::OnlyCreatorUpdate => 'Only creator can update',
            self::OnlyCreatorDelete => 'Only creator can delete',
            self::OnlyTodayData => 'Only today data',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::DashboardView => 'Overview',
            self::ProductsView, self::ProductsCreate, self::ProductsUpdate, self::ProductsDelete,
            self::BarcodesPrint,
            self::CategoriesView, self::CategoriesCreate, self::CategoriesUpdate, self::CategoriesDelete,
            self::BrandsView, self::BrandsCreate, self::BrandsUpdate, self::BrandsDelete,
            self::UnitsView, self::UnitsCreate, self::UnitsUpdate, self::UnitsDelete,
            self::AttributesView, self::AttributesCreate, self::AttributesUpdate, self::AttributesDelete => 'Catalog',
            self::CustomersView, self::CustomersCreate, self::CustomersUpdate, self::CustomersDelete,
            self::SuppliersView, self::SuppliersCreate, self::SuppliersUpdate, self::SuppliersDelete => 'People',
            self::LocationsView, self::LocationsCreate, self::LocationsUpdate, self::LocationsDelete,
            self::StoresView, self::StoresCreate, self::StoresUpdate, self::StoresDelete => 'Locations',
            self::PurchasingView, self::PurchasingCreate, self::PurchasingUpdate, self::PurchasingDelete,
            self::InventoryView, self::InventoryMovementsView, self::InventoryLayersView,
            self::InventoryAdjustmentView, self::InventoryAdjustmentCreate, self::InventoryAdjustmentUpdate, self::InventoryAdjustmentDelete,
            self::InventoryTransferView, self::InventoryTransferCreate, self::InventoryTransferUpdate, self::InventoryTransferDelete => 'Inventory',
            self::SalesView, self::SalesCreate, self::SalesUpdate, self::SalesDelete,
            self::QuickSalesView, self::QuickSalesCreate,
            self::ReturnsView, self::ReturnsCreate, self::ReturnsUpdate, self::ReturnsDelete,
            self::CashRegisterView, self::CashRegisterManage => 'Sales',
            self::SalesReportsView, self::TopSalesReportsView, self::SalesInvoicesReportsView, self::ProfitReportsView, self::MinStockReportsView, self::CurrentStockValueReportsView,
            self::QuickSalesReportsView => 'Reports',
            self::SettingsView, self::SettingsUpdate => 'Settings',
            self::UsersView, self::UsersCreate, self::UsersUpdate, self::UsersDelete,
            self::RolesView, self::RolesCreate, self::RolesUpdate, self::RolesDelete => 'Access',
            self::TaxesView, self::TaxesCreate, self::TaxesUpdate, self::TaxesDelete => 'Taxes',
            self::OnlyCreatorUpdate, self::OnlyCreatorDelete, self::OnlyTodayData => 'Ownership',
        };
    }

    public static function all(): array
    {
        return array_map(fn (Permission $permission) => $permission->value, self::cases());
    }

    public static function admin(): array
    {
        return array_values(array_diff(self::all(), [
            self::OnlyCreatorUpdate->value,
            self::OnlyCreatorDelete->value,
            self::OnlyTodayData->value,
        ]));
    }

    public static function manager(): array
    {
        return [
            self::DashboardView,
            self::ProductsView,
            self::ProductsCreate,
            self::ProductsUpdate,
            self::BarcodesPrint,
            self::CategoriesView, self::CategoriesCreate, self::CategoriesUpdate,
            self::BrandsView, self::BrandsCreate, self::BrandsUpdate,
            self::UnitsView, self::UnitsCreate, self::UnitsUpdate,
            self::AttributesView, self::AttributesCreate, self::AttributesUpdate,
            self::CustomersView, self::CustomersCreate, self::CustomersUpdate,
            self::SuppliersView, self::SuppliersCreate, self::SuppliersUpdate,
            self::LocationsView, self::LocationsCreate, self::LocationsUpdate,
            self::StoresView, self::StoresCreate, self::StoresUpdate,
            self::PurchasingView, self::PurchasingCreate, self::PurchasingUpdate,
            self::InventoryView, self::InventoryMovementsView, self::InventoryLayersView,
            self::InventoryAdjustmentView, self::InventoryAdjustmentCreate, self::InventoryAdjustmentUpdate, self::InventoryAdjustmentDelete,
            self::InventoryTransferView, self::InventoryTransferCreate, self::InventoryTransferUpdate, self::InventoryTransferDelete,
            self::SalesView, self::SalesCreate,
            self::QuickSalesView, self::QuickSalesCreate,
            self::ReturnsView, self::ReturnsCreate,
            self::SalesReportsView, self::SalesQuantityReportsView, self::TopSalesReportsView, self::SalesInvoicesReportsView, self::ProfitReportsView, self::MinStockReportsView, self::CurrentStockValueReportsView,
            self::QuickSalesReportsView,
            self::SettingsView,
            self::TaxesView, self::TaxesCreate,
        ];
    }

    public static function staff(): array
    {
        return [
            self::CategoriesView, self::CategoriesCreate, self::CategoriesUpdate,
            self::BrandsView, self::BrandsCreate, self::BrandsUpdate,
            self::UnitsView, self::UnitsCreate, self::UnitsUpdate,
            self::AttributesView, self::AttributesCreate, self::AttributesUpdate,
            self::CustomersView, self::CustomersCreate, self::CustomersUpdate,
            self::SuppliersView, self::SuppliersCreate, self::SuppliersUpdate,
            self::ProductsView, self::ProductsCreate, self::ProductsUpdate, self::BarcodesPrint,
            self::LocationsView, self::LocationsCreate, self::LocationsUpdate,
            self::StoresView, self::StoresCreate, self::StoresUpdate,
            self::PurchasingView, self::PurchasingCreate, self::PurchasingUpdate,
            self::OnlyCreatorUpdate,
        ];
    }

    public static function cashier(): array
    {
        return [
            self::SalesView, self::SalesCreate, self::SalesUpdate,
            self::ProductsView, self::LocationsView, self::StoresView, self::BrandsView,
            self::UnitsView, self::CategoriesView,
            self::QuickSalesView, self::QuickSalesCreate,
            self::InventoryView, self::InventoryMovementsView, self::InventoryLayersView,
            self::SalesQuantityReportsView,
            self::OnlyCreatorUpdate,
            self::OnlyTodayData,
        ];
    }
}
