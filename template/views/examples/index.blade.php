@extends('ui.layouts.app')

@section('title', 'Records')

@section('page_heading')
    Records
@endsection

@section('content')
    <div class="card admin-surface-card card-green-light card-outline">
        <div class="card-header admin-surface-card__header tenant-list-head">
            <div>
                <h3 class="card-title">Records</h3>
                <p class="admin-card-hint mb-0">Every item on the platform, ready to review or update.</p>
            </div>
            <a href="#" class="btn admin-btn btn-primary">
                <i class="fa fa-plus"></i> Create
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive admin-table-wrap">
                <table class="table table-hover admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Harbor Cafe</td>
                            <td>mina@harbor.test</td>
                            <td><span class="tenant-status tenant-status--success">Active</span></td>
                            <td>
                                <a href="#" class="btn btn-sm admin-table-btn admin-table-btn-view">Details</a>
                                <a href="#" class="btn btn-sm admin-table-btn admin-table-btn-edit">Edit</a>
                                <button type="button" class="btn btn-sm admin-table-btn admin-table-btn-delete">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
