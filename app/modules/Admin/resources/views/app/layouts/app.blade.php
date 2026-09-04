<!DOCTYPE html>
<html>

<head>
    @include('admin::app.partials._head')

    @stack('css')
</head>

<body class="sidebar-mini layout-fixed">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar -->
        @include('admin::app.partials._top_bar')
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        @include('admin::app.partials._left_nav')

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
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
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- any form element -->
                    @yield('hidden_form_element')
                    <!-- /. any form element -->

                    <!-- flash message -->
                    @include('flash::message')
                    <!-- flash message -->

                    <!-- page main content -->
                    @yield('content')
                    <!-- /. page main content -->

                    <!-- loader -->
                    <div class="overlay hidden" id="ajax_spinner">
                        <i class="fa fa-spinner fa-spin fa-3x loader"></i>
                    </div>
                    <!-- ./ loader -->
                </div>

                <!-- modals -->
                @yield('modals')
                <!-- /. modals -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        @include('admin::app.partials._footer')

        <!-- Control Sidebar -->
        @include('admin::app.partials._right_nav')
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    @include('admin::app.partials._scripts')

    @stack('js')

</body>

</html>
