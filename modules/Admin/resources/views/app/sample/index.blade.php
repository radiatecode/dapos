@extends('layouts.app')

@section('title','Sample List')

@push('css')
    <!-- Data tables -->
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
@endpush

@section('page_heading')
    <i class="fa fa-running"></i> Sample List
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item active">Sample</li>
    </ol>
@endsection

@section('content')
    <div class="row">

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Sample Title</h3>
                    <div class="card-header-action float-right">
                        <div class="btn-group">
                            <a class="btn btn-outline-primary" href="#"><i
                                    class="fas fa-plus-circle"></i> New
                            </a>
                            <button type="button" class="btn btn-outline-danger delete_all"><i
                                    class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @component('client.components.errors')@endcomponent
                    @component('client.components.message')@endcomponent
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped server_datatable">
                            <thead>
                            <tr>
                                <th></th>
                                <th>Office</th>
                                <th>Shift Name</th>
                                <th>Working Days</th>
                                <th>Tolerate Time</th>
                                <th>Creator</th>
                                <th>Created At</th>
                                <th>Updater</th>
                                <th>Updated At</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <!-- DataTables -->
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-responsive/js/dataTables.responsive.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('js/fire-swal-delete.js') }}"></script>

    <script>
        $(function () {
            /**
             * delete bulk item
             */
            {{--$('.delete_all').deleteAllConfirm({--}}
            {{--    url: "{{route('promotion.delete.all')}}"--}}
            {{--});--}}

            /**
             * Sample Datatable
             * @type {jQuery}
             */
            let oTable = $('.server_datatable').DataTable({
                processing: true,
                serverSide: true,
                stateSave: true,
                order: [1, 'desc'],
                ajax: {
                    url: '#',
                    type: 'GET'
                },
                columns: [
                    {data: 'bulk', name: 'bulk', orderable: false, searchable: false},
                    {data: 'office.office_name', name: 'office.office_name'},
                    {data: 'shift_name', name: 'shift_name'},
                    {data: 'num_working_days', name: 'num_working_days', orderable: false, searchable: false},
                    {data: 'checkin_tolerate_time', name: 'checkin_tolerate_time', orderable: false, searchable: false},
                    {data: 'creator.name', name: 'creator.name'},
                    {data: 'created_at', name: 'created_at'},
                    {
                        data: 'updater.name', name: 'updater.name', render: function (data, type, row) {
                            return data ? data : null;
                        }
                    },
                    {data: 'updated_at', name: 'updated_at'},
                    {data: 'action', orderable: false, searchable: false}
                ],
                initComplete: function () {
                    // $('.deletable').deleteConfirm();
                }
            });
        })
    </script>
@endpush
