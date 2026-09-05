@php
    $appName = $appName ?? config('app.name', 'Admin');
    $dashboardUrl = $dashboardUrl ?? url('/');
    $profileUrl = $profileUrl ?? url('/profile');
    $brandKicker = $brandKicker ?? 'Console';
    $userName = $userName ?? (auth()->user()->name ?? 'Admin');
    $userRole = $userRole ?? 'Administrator';
    $navigation = $navigation ?? [
        ['type' => 'header', 'label' => 'Overview'],
        [
            'type' => 'item',
            'label' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'href' => $dashboardUrl,
            'is_active' => true,
            'is_open' => false,
            'children' => [],
        ],
        ['type' => 'header', 'label' => 'Records'],
        [
            'type' => 'item',
            'label' => 'Items',
            'icon' => 'fas fa-layer-group',
            'href' => '#',
            'is_active' => false,
            'is_open' => true,
            'children' => [
                ['type' => 'item', 'label' => 'List', 'icon' => 'fas fa-list', 'href' => '#', 'is_active' => false, 'is_open' => false, 'children' => []],
                ['type' => 'item', 'label' => 'New', 'icon' => 'fas fa-plus-circle', 'href' => '#', 'is_active' => false, 'is_open' => false, 'children' => []],
            ],
        ],
    ];
@endphp

<aside class="main-sidebar admin-sidebar elevation-4">
    <a href="{{ $dashboardUrl }}" class="brand-link admin-brand">
        <span class="admin-brand__mark">
            @if (! empty($brandLogo))
                <img src="{{ $brandLogo }}" alt="Logo" class="brand-image">
            @else
                <span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($appName, 0, 1)) }}</span>
            @endif
        </span>
        <span class="brand-text admin-brand__copy">
            <small>{{ $brandKicker }}</small>
            <strong>{{ $appName }}</strong>
        </span>
    </a>

    <div class="sidebar">
        <div class="user-panel admin-user-panel">
            <div class="image">
                @if (! empty($userAvatar))
                    <img src="{{ $userAvatar }}" class="img-circle elevation-2" alt="User Image">
                @else
                    <span class="admin-userchip__avatar">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($userName, 0, 1)) }}
                    </span>
                @endif
            </div>
            <div class="info">
                <a href="{{ $profileUrl }}" class="d-block">{{ $userName }}</a>
                <span>{{ $userRole }}</span>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                @include('ui.partials._nav_items', ['items' => $navigation])
            </ul>
        </nav>
    </div>
</aside>
