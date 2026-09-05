@extends('admin::app.layouts.app')

@include('admin::app.partials._datatables')

@section('title', 'Subscriptions')

@section('page_heading')
    Subscriptions
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active">Subscriptions</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card admin-surface-card card-green-light card-outline">
                <div class="card-header admin-surface-card__header">
                    <div>
                        <h3 class="card-title">Subscriptions</h3>
                        <p class="admin-card-hint mb-0">Tenant trials, active plans, and billing periods.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive admin-table-wrap">
                        {{ $dataTable->table(['class' => 'table table-hover admin-table'], true) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    {{ $dataTable->scripts() }}
@endpush
