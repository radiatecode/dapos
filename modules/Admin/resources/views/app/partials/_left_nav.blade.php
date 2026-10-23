<aside class="main-sidebar admin-sidebar elevation-4">
    <a href="{{ route('admin.dashboard') }}" class="brand-link admin-brand">
        <span class="admin-brand__mark">
            <img src="{{ asset('vendor/admin/dapos-new-thumb.jpg') }}" alt="" class="brand-image">
        </span>
        <span class="brand-text admin-brand__copy">
            <strong>DaPOS</strong>
            <small>Point Of Sale</small>
        </span>
    </a>

    <div class="sidebar">
        <div class="user-panel admin-user-panel">
            <div class="image">
                <img src="{{ auth('admin')->user()->avatar_path ?? asset('vendor/admin/media/no-avatar.png') }}"
                    class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="{{ route('admin.profile') }}" class="d-block">
                    {{ auth('admin')->check() ? auth('admin')->user()->name : 'No User' }}
                </a>
                <span>Platform admin</span>
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
