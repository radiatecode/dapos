<?php

namespace DA\Admin\Enums;

enum AdminPermission: string
{
    case DashboardView = 'dashboard.view';
    case PermissionsView = 'permissions.view';
    case TenantsView = 'tenants.view';
    case TenantsCreate = 'tenants.create';
    case TenantsEdit = 'tenants.edit';
    case TenantsDelete = 'tenants.delete';
    case UsersView = 'users.view';
    case PlansView = 'plans.view';
    case FeaturesView = 'features.view';
    case AddonsView = 'addons.view';
    case SubscriptionsView = 'subscriptions.view';
    case InvoicesView = 'invoices.view';
    case PaymentsView = 'payments.view';
    case CouponsView = 'coupons.view';
    case UsageView = 'usage.view';
    case SubscriptionEventsView = 'subscription-events.view';
    case SettingsView = 'settings.view';

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'View dashboard',
            self::PermissionsView => 'View permissions',
            self::TenantsView => 'View tenants',
            self::TenantsCreate => 'Create tenants',
            self::TenantsEdit => 'Edit tenants',
            self::TenantsDelete => 'Delete tenants',
            self::UsersView => 'View users',
            self::PlansView => 'View plans',
            self::FeaturesView => 'View features',
            self::AddonsView => 'View add-ons',
            self::SubscriptionsView => 'View subscriptions',
            self::InvoicesView => 'View invoices',
            self::PaymentsView => 'View payments',
            self::CouponsView => 'View coupons',
            self::UsageView => 'View usage',
            self::SubscriptionEventsView => 'View subscription events',
            self::SettingsView => 'View system settings',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::DashboardView => 'Overview',
            self::PermissionsView => 'Account',
            self::TenantsView, self::TenantsCreate, self::TenantsEdit, self::TenantsDelete, self::UsersView => 'Tenancy',
            self::PlansView, self::FeaturesView, self::AddonsView => 'Catalog',
            self::SubscriptionsView, self::InvoicesView, self::PaymentsView, self::CouponsView => 'Billing',
            self::UsageView, self::SubscriptionEventsView => 'Operations',
            self::SettingsView => 'System',
        };
    }
}
