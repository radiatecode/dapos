@extends('layouts.app')

@section('title','Sample')

@section('page_heading')
    <i class="fa fa-edit"></i> Sample
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item"><a href="#">Sample</a></li>
        <li class="breadcrumb-item active">Sample</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title">Sample Card Title</h3>
                </div>
                <form id="confirm-form" action="#" method="post" autocomplete="off">
                    @csrf
                    @method('put')
                    <div class="card-body">
                        @component('client.components.errors')@endcomponent
                        <div class="row">
                            <div class="col-md-12 col-lg-12 col-sm-12">
                                <div class="form-group">
                                    <label class="col-form-label"><i class="fas fa-pen-square"></i> Sample
                                        Select</label>
                                    <select id="select_sample" name="select_sample" class="form-control" required>
                                        <option value="">-- Please Select --</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="col-form-label"><i class="fas fa-pen-square"></i> Input 1</label>
                                    <input type="text" class="form-control" id="input_one" name="input_one"
                                           placeholder="Enter...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-outline-info"><i class="fa fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>

    </script>
@endpush
