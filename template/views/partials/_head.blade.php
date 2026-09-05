@php
    $uiAsset = $uiAsset ?? 'vendor/ui';
@endphp

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>@yield('title') | {{ $appName ?? config('app.name', 'Admin') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="app-name" content="{{ $appName ?? config('app.name', 'Admin') }}">
<meta name="app-url" content="{{ config('app.url') }}">
<meta name="color-scheme" content="dark light">
<script>
    (function () {
        try {
            var theme = localStorage.getItem('admin-theme');

            if (theme !== 'dark' && theme !== 'light') {
                theme = 'dark';
            }

            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.classList.toggle('dark', theme === 'dark');
        } catch (error) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    })();
</script>

@yield('metas')

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="{{ asset($uiAsset.'/css/custom.css') }}">
<script src="{{ asset($uiAsset.'/js/theme.js') }}"></script>
