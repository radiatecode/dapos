@extends('ui.layouts.app')

@section('title', 'Dashboard')

@section('page_heading')
    Dashboard
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4 mb-3">
            <x-ui::stat-card label="Active records" value="128" hint="This week" icon="fas fa-layer-group" />
        </div>
        <div class="col-md-4 mb-3">
            <x-ui::stat-card label="Pending" value="14" hint="Needs review" icon="fas fa-clock" />
        </div>
        <div class="col-md-4 mb-3">
            <x-ui::stat-card label="Revenue" value="$24.8k" hint="Last 30 days" icon="fas fa-coins" />
        </div>
    </div>

    <x-ui::boot-card title="Recent activity">
        <x-slot:header>
            <p class="admin-card-hint mb-0">A compact table using the same surface language.</p>
        </x-slot:header>

        <div class="table-responsive admin-table-wrap">
            <table class="table table-hover admin-table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Harbor Cafe</td>
                        <td><span class="tenant-status tenant-status--success">Active</span></td>
                        <td>2 hours ago</td>
                        <td>
                            <a href="#" class="btn btn-sm admin-table-btn admin-table-btn-view">Details</a>
                        </td>
                    </tr>
                    <tr>
                        <td>Blue Roastery</td>
                        <td><span class="tenant-status tenant-status--danger">Paused</span></td>
                        <td>Yesterday</td>
                        <td>
                            <a href="#" class="btn btn-sm admin-table-btn admin-table-btn-edit">Edit</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-ui::boot-card>
@endsection
