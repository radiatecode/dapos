<?php

namespace DA\Admin\Support;

use DA\Admin\Enums\AdminPermission;
use DA\Admin\Models\AdminUser;

class AdminNavigation
{
    /**
     * @return list<array{label: string, icon: string, permission: ?string, route: ?string, active: list<string>, soon: bool}>
     */
    public function items(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'icon' => 'bi-grid-1x2-fill',
                'permission' => null,
                'route' => 'admin.dashboard',
                'active' => ['admin.dashboard'],
                'soon' => false,
            ],
            [
                'label' => 'Tenants',
                'icon' => 'bi-building',
                'permission' => AdminPermission::TenantsView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Users',
                'icon' => 'bi-people',
                'permission' => AdminPermission::UsersView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Plans',
                'icon' => 'bi-layers',
                'permission' => AdminPermission::PlansView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Features',
                'icon' => 'bi-stars',
                'permission' => AdminPermission::FeaturesView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Add-ons',
                'icon' => 'bi-puzzle',
                'permission' => AdminPermission::AddonsView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Subscriptions',
                'icon' => 'bi-arrow-repeat',
                'permission' => AdminPermission::SubscriptionsView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Invoices',
                'icon' => 'bi-receipt',
                'permission' => AdminPermission::InvoicesView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Payments',
                'icon' => 'bi-credit-card',
                'permission' => AdminPermission::PaymentsView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Coupons',
                'icon' => 'bi-ticket-perforated',
                'permission' => AdminPermission::CouponsView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Usage',
                'icon' => 'bi-graph-up-arrow',
                'permission' => AdminPermission::UsageView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Events',
                'icon' => 'bi-activity',
                'permission' => AdminPermission::SubscriptionEventsView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Settings',
                'icon' => 'bi-gear',
                'permission' => AdminPermission::SettingsView->value,
                'route' => null,
                'active' => [],
                'soon' => true,
            ],
            [
                'label' => 'Permissions',
                'icon' => 'bi-shield-lock',
                'permission' => AdminPermission::PermissionsView->value,
                'route' => 'admin.permissions',
                'active' => ['admin.permissions'],
                'soon' => false,
            ],
        ];
    }

    /**
     * @return list<array{label: string, icon: string, permission: ?string, route: ?string, active: list<string>, soon: bool}>
     */
    public function visibleFor(AdminUser $admin): array
    {
        return array_values(array_filter(
            $this->items(),
            fn (array $item): bool => $item['permission'] === null || $admin->hasAdminPermission($item['permission']),
        ));
    }
}
