<?php

namespace DA\Admin\Enums;

enum Features: string
{
    // boolean type features
    case POS = 'pos';
    case INVENTORY = 'inventory';
    case INVENTORY_MOVEMENTS = 'inventory_movements';
    case INVENTORY_ADJUSTMENTS = 'inventory_adjustments';
    case INVENTORY_TRANSFERS = 'inventory_transfers';
    case INVENTORY_LAYERS = 'inventory_layers';
    case SALES = 'sales';
    case QUICK_SALES = 'quick_sales';
    case PURCHASES = 'purchases';
    case PURCHASE_RETURNS = 'purchase_returns';
    case CUSTOMERS = 'customers';
    case PRODUCT_VARIANTS = 'product_variants';
    case TAX_SETTINGS = 'tax_settings';
    case PRINTER_SETTINGS = 'printer_settings';
    case CURRENCY_SETTINGS = 'currency_settings';
    case POS_SETTINGS = 'pos_settings';

    // boolean type report feature
    case SALES_REPORT = 'sales_report';
    case SALES_QUANTITY_REPORT = 'sales_quantity_report';
    case SALES_INVOICE_REPORT = 'sales_invoice_report';
    case QUICK_SALE_REPORT = 'quick_sale_report';
    case TOP_SALE_REPORT = 'tops_sale_report';
    case PROFIT_REPORT = 'profit_report';
    case LOW_STOCK_REPORT = 'low_stock_report';
    case CURRENT_STOCK_REPORT = 'current_stock_report';

    // limit type features
    case STORES = 'stores';
    case PRODUCTS = 'products';
    case USERS = 'users';
    case ROLES = 'roles';
    case ADMIN_ROLE = 'admin_role';

    public function label()
    {
        return match ($this) {
            self::POS => 'POS',
            self::INVENTORY => 'Inventory',
            self::INVENTORY_MOVEMENTS => 'Inventory Movements',
            self::INVENTORY_ADJUSTMENTS => 'Inventory Adjustments',
            self::INVENTORY_TRANSFERS => 'Inventory Transfers',
            self::INVENTORY_LAYERS => 'Inventory Layers',
            self::SALES => 'Sales',
            self::QUICK_SALES => 'Quick Sales',
            self::PURCHASES => 'Purchases',
            self::PURCHASE_RETURNS => 'Purchase Return',
            self::CUSTOMERS => 'Customers',
            self::PRODUCT_VARIANTS => 'Product Variants',
            self::TAX_SETTINGS => 'Tax Settings',
            self::PRINTER_SETTINGS => 'Printer Settings',
            self::CURRENCY_SETTINGS => 'Currency Settings',
            self::POS_SETTINGS => 'POS Settings',
            self::SALES_REPORT => 'Sales (Reports)',
            self::SALES_QUANTITY_REPORT => 'Sales Quantity (Reports)',
            self::SALES_INVOICE_REPORT => 'Sales Invoice (Reports)',
            self::QUICK_SALE_REPORT => 'Quick Sale (Reports)',
            self::TOP_SALE_REPORT => 'Top Sale (Reports)',
            self::PROFIT_REPORT => 'Profit (Reports)',
            self::LOW_STOCK_REPORT => 'Low Stock (Reports)',
            self::CURRENT_STOCK_REPORT => 'Current Stock (Reports)',

            // limit type features
            self::STORES => 'Stores',
            self::PRODUCTS => 'Products',
            self::USERS => 'Users',
            self::ROLES => 'Roles',
            self::ADMIN_ROLE => 'Admin Role',
        };
    }

    public function type(): string
    {
        return match ($this) {
            self::SALES_REPORT, self::SALES_QUANTITY_REPORT, self::SALES_INVOICE_REPORT, self::QUICK_SALE_REPORT, self::TOP_SALE_REPORT, self::PROFIT_REPORT, self::LOW_STOCK_REPORT, self::CURRENT_STOCK_REPORT => 'report',
            self::STORES, self::PRODUCTS, self::USERS, self::ROLES, self::ADMIN_ROLE => 'limit',
            default => 'boolean',
        };
    }

    public function isBoolean(): bool
    {
        return $this->type() !== 'limit';
    }

    public function isLimit(): bool
    {
        return $this->type() === 'limit';
    }

    /**
     * @return array<string, array{label: string, type: string}>
     */
    public static function allFeatures(): array
    {
        $features = [];

        foreach (self::cases() as $feature) {
            $features[$feature->value] = [
                'label' => $feature->label(),
                'type' => $feature->type(),
            ];
        }

        return $features;
    }

    public static function moduleFeatures(): array
    {
        return [
            self::POS,
            self::INVENTORY,
            self::INVENTORY_MOVEMENTS,
            self::INVENTORY_ADJUSTMENTS,
            self::INVENTORY_TRANSFERS,
            self::INVENTORY_LAYERS,
            self::SALES,
            self::QUICK_SALES,
            self::PURCHASES,
            self::PURCHASE_RETURNS,
            self::CUSTOMERS,
            self::PRODUCT_VARIANTS,
            self::TAX_SETTINGS,
            self::PRINTER_SETTINGS,
            self::CURRENCY_SETTINGS,
            self::POS_SETTINGS,
        ];
    }

    public static function reportFeatures(): array
    {
        return [
            self::SALES_REPORT,
            self::SALES_QUANTITY_REPORT,
            self::SALES_INVOICE_REPORT,
            self::QUICK_SALE_REPORT,
            self::TOP_SALE_REPORT,
            self::PROFIT_REPORT,
            self::LOW_STOCK_REPORT,
            self::CURRENT_STOCK_REPORT,
        ];
    }

    public static function limitFeatures(): array
    {
        return [
            self::STORES,
            self::PRODUCTS,
            self::USERS,
            self::ROLES,
            self::ADMIN_ROLE,
        ];
    }
}
