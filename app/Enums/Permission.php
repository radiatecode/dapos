<?php

namespace App\Enums;

enum Permission: string
{
    case DashboardView = 'dashboard.view';

    case ProductsView = 'products.view';
    case ProductsCreate = 'products.create';
    case ProductsUpdate = 'products.update';
    case ProductsDelete = 'products.delete';

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
    case InventoryCreate = 'inventory.create';
    case InventoryUpdate = 'inventory.update';
    case InventoryDelete = 'inventory.delete';

    case SalesView = 'sales.view';
    case SalesCreate = 'sales.create';
    case SalesUpdate = 'sales.update';
    case SalesDelete = 'sales.delete';

    case ReturnsView = 'returns.view';
    case ReturnsCreate = 'returns.create';
    case ReturnsUpdate = 'returns.update';
    case ReturnsDelete = 'returns.delete';

    case CashRegisterView = 'cash-register.view';
    case CashRegisterManage = 'cash-register.manage';

    case ReportsView = 'reports.view';

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

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'View dashboard',
            self::ProductsView => 'View products',
            self::ProductsCreate => 'Create products',
            self::ProductsUpdate => 'Update products',
            self::ProductsDelete => 'Delete products',
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
            self::InventoryCreate => 'Create inventory adjustments',
            self::InventoryUpdate => 'Update inventory',
            self::InventoryDelete => 'Delete inventory adjustments',
            self::SalesView => 'View sales',
            self::SalesCreate => 'Create sales',
            self::SalesUpdate => 'Update sales',
            self::SalesDelete => 'Delete sales',
            self::ReturnsView => 'View returns',
            self::ReturnsCreate => 'Create returns',
            self::ReturnsUpdate => 'Update returns',
            self::ReturnsDelete => 'Delete returns',
            self::CashRegisterView => 'View cash register',
            self::CashRegisterManage => 'Manage cash register',
            self::ReportsView => 'View reports',
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
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::DashboardView => 'Overview',
            self::ProductsView, self::ProductsCreate, self::ProductsUpdate, self::ProductsDelete,
            self::CategoriesView, self::CategoriesCreate, self::CategoriesUpdate, self::CategoriesDelete,
            self::BrandsView, self::BrandsCreate, self::BrandsUpdate, self::BrandsDelete,
            self::UnitsView, self::UnitsCreate, self::UnitsUpdate, self::UnitsDelete,
            self::AttributesView, self::AttributesCreate, self::AttributesUpdate, self::AttributesDelete => 'Catalog',
            self::CustomersView, self::CustomersCreate, self::CustomersUpdate, self::CustomersDelete,
            self::SuppliersView, self::SuppliersCreate, self::SuppliersUpdate, self::SuppliersDelete => 'People',
            self::LocationsView, self::LocationsCreate, self::LocationsUpdate, self::LocationsDelete,
            self::StoresView, self::StoresCreate, self::StoresUpdate, self::StoresDelete => 'Locations',
            self::PurchasingView, self::PurchasingCreate, self::PurchasingUpdate, self::PurchasingDelete,
            self::InventoryView, self::InventoryCreate, self::InventoryUpdate, self::InventoryDelete => 'Inventory',
            self::SalesView, self::SalesCreate, self::SalesUpdate, self::SalesDelete,
            self::ReturnsView, self::ReturnsCreate, self::ReturnsUpdate, self::ReturnsDelete,
            self::CashRegisterView, self::CashRegisterManage => 'Sales',
            self::ReportsView => 'Reports',
            self::SettingsView, self::SettingsUpdate => 'Settings',
            self::UsersView, self::UsersCreate, self::UsersUpdate, self::UsersDelete,
            self::RolesView, self::RolesCreate, self::RolesUpdate, self::RolesDelete => 'Access',
        };
    }
}
