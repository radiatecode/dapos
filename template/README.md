# Admin UI template

Reusable forest-green + gold admin shell with dark mode by default. Copy this folder into another Laravel project, or open `html/preview.html` in a browser.

## What is included

- `assets/css/custom.css` — cards, buttons, forms, sidebar, top bar, dark mode
- `assets/css/guest.css` — login / guest layout
- `assets/js/theme.js` — dark/light toggle, `localStorage` key `admin-theme`
- `assets/js/form.tabs.js` — jump to the first invalid tab on a tabbed form
- `views/` — Blade layouts, partials, components, and example pages
- `html/` — standalone HTML you can open without Laravel

## Install into a Laravel app

1. Copy assets:

```bash
cp -R template/assets/* public/vendor/ui/
```

2. Copy views to `resources/views/ui/`.

3. Register the view namespace and components in a service provider:

```php
View::addNamespace('ui', resource_path('views/ui'));
Blade::anonymousComponentPath(resource_path('views/ui/components'), 'ui');
```

4. Share chrome URLs (optional):

```php
View::share([
    'dashboardUrl' => route('dashboard'),
    'profileUrl' => route('profile'),
    'logoutUrl' => route('logout'),
    'brandKicker' => 'Console',
]);
```

5. Pass `$navigation` into the sidebar. Each item:

```php
[
    'type' => 'header', // or omit for a link
    'label' => 'Dashboard',
    'icon' => 'fas fa-tachometer-alt',
    'href' => route('dashboard'),
    'is_active' => request()->routeIs('dashboard'),
    'is_open' => false,
    'children' => [],
]
```

6. Extend pages with `@extends('ui.layouts.app')`.

## Theme

Dark is the default. Users switch to light from the sun icon. Preference is stored as `admin-theme` in `localStorage`.

## Dependencies

Layouts expect AdminLTE 3, Bootstrap 5, jQuery, Font Awesome 5, and Select2. The Blade `_head` / `_scripts` partials load those from CDNs so you do not need this project's `public/vendor/admin` tree.
