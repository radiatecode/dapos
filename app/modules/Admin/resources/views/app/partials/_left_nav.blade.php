<aside class="main-sidebar sidebar-light-olive elevation-4">
    <a href="{{ route('admin.dashboard') }}" class="brand-link">
        <img src="{{ asset('vendor/admin/pos-logo-thumb.jpeg') }}" alt="Logo"
            class="brand-image img-circle elevation-2" style="opacity: .8">
        <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ auth('admin')->user()->avatar_path ?? asset('vendor/admin/media/no-avatar.png') }}"
                    class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="{{ route('admin.profile') }}" class="d-block">
                    {{ auth('admin')->check() ? auth('admin')->user()->name : 'No User' }}
                </a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                @include('admin::app.partials._nav_items', ['items' => $navigation ?? []])
            </ul>
        </nav>
    </div>
</aside>
