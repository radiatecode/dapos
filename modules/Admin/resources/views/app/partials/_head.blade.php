<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>@yield('title') | {{ config('app.name') }}</title>
<!-- Tell the browser to be responsive to screen width -->
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="app-name" content="{{ config('app.name') }}">
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

<!-- Google Font: Plus Jakarta Sans + Source Sans Pro -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Source+Sans+Pro:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap">

<!-- Font Awesome -->
{{-- <link rel="stylesheet" href="{{asset('js/plugins/fontawesome-free/css/all.min.css')}}"> --}}
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
    integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

<!-- Ionicons -->
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

<!-- admin lte css -->
<link rel="stylesheet" href="{{ asset('vendor/admin/css/adminlte.css') }}">

<!-- overlayScrollbars -->
<link rel="stylesheet" href="{{ asset('vendor/admin/js/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">

<!-- sweet alert -->
<link rel="stylesheet" href="{{ asset('vendor/admin/js/plugins/sweetalert2/sweetalert2.css') }}">
{{-- <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}"> --}}

<!-- select2 -->
<link rel="stylesheet" href="{{ asset('vendor/admin/js/plugins/select2/css/select2.css') }}">
<link rel="stylesheet"
    href="{{ asset('vendor/admin/js/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

<!-- toast -->
<link rel="stylesheet" href="{{ asset('vendor/admin/js/plugins/toastr/toastr.min.css') }}">

<!-- date picker -->
<link rel="stylesheet" href="{{ asset('vendor/admin/js/plugins/bootstrap-datepicker/datepicker.css') }}">

<!-- timepicker -->
<link rel="stylesheet"
    href="{{ asset('vendor/admin/js/plugins/bootstrap-timepicker/css/bootstrap-timepicker.min.css') }}">

<!-- time picker -->
{{-- <link rel="stylesheet" href="{{ asset('js/plugins/wickedpicker/dist/wickedpicker.min.css')  }}"> --}}
{{-- <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css"> --}}

<!-- custom css -->
<link rel="stylesheet" href="{{ asset('vendor/admin/css/custom.css') }}?v={{ filemtime(public_path('vendor/admin/css/custom.css')) }}">
<script src="{{ asset('vendor/admin/js/theme.js') }}?v={{ filemtime(public_path('vendor/admin/js/theme.js')) }}"></script>

<style>
    .hidden {
        display: none;
    }

    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 50;
        background: rgba(255, 255, 255, 0.7);
        border-radius: 3px;
    }

    .loader {
        position: relative;
        top: 40%;
        left: 50%;
        margin-right: -50%;
    }

    .parsley-required,
    .required {
        color: red;
    }
</style>
