@extends('admin::layouts.app')

@section('title', 'Tenants')

@section('content')
    <x-admin::page-header kicker="Tenants" title="Tenants" subtitle="Manage the tenants of the provider.">
        <x-slot:actions>
            <a href="{{ route('admin.tenants.create') }}" class="btn btn-admin">
                <i class="bi bi-plus-lg me-1"></i> New tenant
            </a>
        </x-slot:actions>
    </x-admin::page-header>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-admin::card title="Tenants">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </x-admin::card>
        </div>
    </div>
@endsection
