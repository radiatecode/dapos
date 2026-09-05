<nav class="main-header navbar navbar-expand admin-topbar">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link admin-topbar__icon-link" data-widget="pushmenu" href="#" role="button"
                aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('admin.dashboard') }}" class="nav-link admin-topbar__text-link">Dashboard</a>
        </li>
    </ul>

    <form class="form-inline admin-search d-none d-md-flex" role="search" onsubmit="return false;">
        <div class="input-group input-group-sm">
            <input class="form-control form-control-navbar" type="search" placeholder="Search tenants, users..."
                aria-label="Search">
            <div class="input-group-append">
                <button class="btn btn-navbar" type="submit" aria-label="Search">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </form>

    <ul class="navbar-nav ml-auto ms-auto align-items-center">
        <li class="nav-item">
            <button type="button" id="theme-mode" class="admin-theme-toggle" title="Switch to light mode"
                aria-pressed="true" aria-label="Switch to light mode">
                <i id="theme-mode-icon" class="fas fa-sun"></i>
            </button>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link admin-userchip" data-bs-toggle="dropdown" href="#" role="button"
                aria-expanded="false">
                <span class="admin-userchip__avatar">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth('admin')->user()->name ?? 'A', 0, 1)) }}
                </span>
                <span class="admin-userchip__name d-none d-md-inline">
                    {{ auth('admin')->user()->name ?? 'Admin' }}
                </span>
                <i class="fas fa-chevron-down d-none d-md-inline"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right dropdown-menu-end admin-topbar__menu">
                <a href="{{ route('admin.profile') }}" class="dropdown-item">
                    <i class="fas fa-user mr-2 me-2"></i> Profile
                </a>
                <div class="dropdown-divider"></div>
                <button type="button" class="dropdown-item text-danger" onclick="logout()">
                    <i class="fas fa-power-off mr-2 me-2"></i> Logout
                </button>
            </div>
        </li>
    </ul>
</nav>
