<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">

<head>
    @include('ui.partials._head')

    @stack('css')
</head>

<body class="sidebar-mini layout-fixed admin-shell dark-mode">
    <div class="wrapper">
        @include('ui.partials._top_bar')

        @include('ui.partials._left_nav')

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>@yield('page_heading')</h1>
                        </div>
                        <div class="col-sm-6">
                            @yield('breadcrumbs')
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    @yield('hidden_form_element')

                    @if (session('success'))
                        <x-ui::alert type="success">{{ session('success') }}</x-ui::alert>
                    @endif
                    @if (session('error'))
                        <x-ui::alert type="danger">{{ session('error') }}</x-ui::alert>
                    @endif

                    @yield('content')

                    <div class="overlay hidden" id="ajax_spinner">
                        <i class="fa fa-spinner fa-spin fa-3x loader"></i>
                    </div>
                </div>

                @yield('modals')
            </section>
        </div>

        @include('ui.partials._footer')
        @include('ui.partials._right_nav')
    </div>

    @include('ui.partials._scripts')

    @stack('js')
</body>

</html>
