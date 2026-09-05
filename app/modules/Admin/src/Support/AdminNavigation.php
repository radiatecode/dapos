<?php

namespace DA\Admin\Support;

use DA\Admin\Enums\AdminPermission;
use DA\Admin\Models\AdminUser;
use Illuminate\Support\Facades\Route;

class AdminNavigation
{
    /**
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        return [
            [
                'header' => 'Overview',
            ],
            [
                'label' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'permission' => null,
                'route' => 'admin.dashboard',
                'active' => ['admin.dashboard'],
            ],
            [
                'header' => 'Tenancy',
            ],
            [
                'label' => 'Tenants',
                'icon' => 'fas fa-building',
                'permission' => AdminPermission::TenantsView->value,
                'route' => null,
                'active' => ['admin.tenants.*'],
                'children' => [
                    [
                        'label' => 'List',
                        'icon' => 'fas fa-list',
                        'permission' => AdminPermission::TenantsView->value,
                        'route' => 'admin.tenants.index',
                        'active' => ['admin.tenants.index'],
                    ],
                    [
                        'label' => 'New',
                        'icon' => 'fas fa-plus-circle',
                        'permission' => AdminPermission::TenantsCreate->value,
                        'route' => 'admin.tenants.create',
                        'active' => ['admin.tenants.create'],
                    ],
                ],
            ],
            [
                'label' => 'Users',
                'icon' => 'fas fa-users',
                'permission' => AdminPermission::UsersView->value,
                'route' => null,
                'active' => [],
            ],
            [
                'header' => 'Catalog',
            ],
            [
                'label' => 'Plans',
                'icon' => 'fas fa-layer-group',
                'permission' => AdminPermission::PlansView->value,
                'route' => null,
                'active' => ['admin.plans.*'],
                'children' => [
                    [
                        'label' => 'List',
                        'icon' => 'fas fa-list',
                        'permission' => AdminPermission::PlansView->value,
                        'route' => 'admin.plans.index',
                        'active' => ['admin.plans.index'],
                    ],
                    [
                        'label' => 'New',
                        'icon' => 'fas fa-plus-circle',
                        'permission' => AdminPermission::PlansView->value,
                        'route' => 'admin.plans.create',
                        'active' => ['admin.plans.create'],
                    ],
                ],
            ],
            [
                'label' => 'Features',
                'icon' => 'fas fa-star',
                'permission' => AdminPermission::FeaturesView->value,
                'route' => 'admin.features.index',
                'active' => ['admin.features.*'],
            ],
            [
                'label' => 'Add-ons',
                'icon' => 'fas fa-puzzle-piece',
                'permission' => AdminPermission::AddonsView->value,
                'route' => 'admin.addons.index',
                'active' => ['admin.addons.*'],
            ],
            [
                'header' => 'Billing',
            ],
            [
                'label' => 'Subscriptions',
                'icon' => 'fas fa-sync-alt',
                'permission' => AdminPermission::SubscriptionsView->value,
                'route' => null,
                'active' => ['admin.subscriptions.*'],
                'children' => [
                    [
                        'label' => 'List',
                        'icon' => 'fas fa-list',
                        'permission' => AdminPermission::SubscriptionsView->value,
                        'route' => 'admin.subscriptions.index',
                        'active' => ['admin.subscriptions.index'],
                    ],
                    [
                        'label' => 'New',
                        'icon' => 'fas fa-plus-circle',
                        'permission' => AdminPermission::SubscriptionsCreate->value,
                        'route' => 'admin.subscriptions.create',
                        'active' => ['admin.subscriptions.create'],
                    ],
                ],
            ],
            [
                'label' => 'Invoices',
                'icon' => 'fas fa-file-invoice',
                'permission' => AdminPermission::InvoicesView->value,
                'route' => null,
                'active' => [],
            ],
            [
                'label' => 'Payments',
                'icon' => 'fas fa-credit-card',
                'permission' => AdminPermission::PaymentsView->value,
                'route' => null,
                'active' => [],
            ],
            [
                'label' => 'Coupons',
                'icon' => 'fas fa-ticket-alt',
                'permission' => AdminPermission::CouponsView->value,
                'route' => null,
                'active' => [],
            ],
            [
                'header' => 'Operations',
            ],
            [
                'label' => 'Usage',
                'icon' => 'fas fa-chart-line',
                'permission' => AdminPermission::UsageView->value,
                'route' => null,
                'active' => [],
            ],
            [
                'label' => 'Events',
                'icon' => 'fas fa-bolt',
                'permission' => AdminPermission::SubscriptionEventsView->value,
                'route' => 'admin.subscription-events.index',
                'active' => ['admin.subscription-events.*'],
            ],
            [
                'header' => 'System',
            ],
            [
                'label' => 'Settings',
                'icon' => 'fas fa-cog',
                'permission' => AdminPermission::SettingsView->value,
                'route' => null,
                'active' => [],
            ],
            [
                'label' => 'Permissions',
                'icon' => 'fas fa-shield-alt',
                'permission' => AdminPermission::PermissionsView->value,
                'route' => 'admin.permissions',
                'active' => ['admin.permissions'],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function visibleFor(AdminUser $admin): array
    {
        $items = [];
        $pendingHeader = null;

        foreach ($this->items() as $item) {
            if ($this->isHeaderOnly($item)) {
                $pendingHeader = $item['header'];

                continue;
            }

            $presented = $this->present($item, $admin);

            if ($presented === null) {
                continue;
            }

            if (isset($item['header']) && is_string($item['header']) && $item['header'] !== '') {
                $pendingHeader = $item['header'];
            }

            if (is_string($pendingHeader) && $pendingHeader !== '') {
                $items[] = [
                    'type' => 'header',
                    'label' => $pendingHeader,
                ];
                $pendingHeader = null;
            }

            $items[] = $presented;
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isHeaderOnly(array $item): bool
    {
        return isset($item['header']) && ! isset($item['label']) && ! isset($item['children']);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function present(array $item, AdminUser $admin, bool $forceVisible = false): ?array
    {
        /** @var list<array<string, mixed>> $rawChildren */
        $rawChildren = $item['children'] ?? [];
        $isTree = $rawChildren !== [];

        if ($isTree) {
            if (! $this->isTreeVisible($admin, $item, $rawChildren)) {
                return null;
            }

            $children = [];

            foreach ($rawChildren as $child) {
                $presented = $this->present($child, $admin, true);

                if ($presented !== null) {
                    $children[] = $presented;
                }
            }

            if ($children === []) {
                return null;
            }
        } else {
            if (! $forceVisible && ! $this->isAllowed($admin, $item['permission'] ?? null)) {
                return null;
            }

            $children = [];
        }

        $isActive = $this->matchesActive($item, $children);

        return [
            'type' => 'item',
            'label' => $item['label'],
            'icon' => $item['icon'],
            'href' => $this->href($item['route'] ?? null),
            'is_active' => $isActive,
            'is_open' => $isTree && $isActive,
            'children' => $children,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<array<string, mixed>>  $children
     */
    private function isTreeVisible(AdminUser $admin, array $item, array $children): bool
    {
        if ($this->isAllowed($admin, $item['permission'] ?? null)) {
            return true;
        }

        foreach ($children as $child) {
            if ($this->isAllowed($admin, $child['permission'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function isAllowed(AdminUser $admin, ?string $permission): bool
    {
        return $permission === null || $admin->hasAdminPermission($permission);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<array{is_active: bool}>  $children
     */
    private function matchesActive(array $item, array $children): bool
    {
        /** @var list<string> $patterns */
        $patterns = $item['active'] ?? [];

        if ($patterns !== [] && request()->routeIs(...$patterns)) {
            return true;
        }

        foreach ($children as $child) {
            if ($child['is_active']) {
                return true;
            }
        }

        $route = $item['route'] ?? null;

        if (is_string($route) && $route !== '' && ! $this->isUrl($route) && Route::has($route)) {
            return request()->routeIs($route);
        }

        return false;
    }

    private function href(?string $route): string
    {
        if (! is_string($route) || $route === '') {
            return '#';
        }

        if ($this->isUrl($route)) {
            return $route;
        }

        return Route::has($route) ? route($route) : '#';
    }

    private function isUrl(string $route): bool
    {
        return str_starts_with($route, 'http://')
            || str_starts_with($route, 'https://')
            || str_starts_with($route, '/');
    }
}
