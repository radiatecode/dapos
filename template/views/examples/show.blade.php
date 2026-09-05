@extends('ui.layouts.app')

@section('title', 'Harbor Cafe')

@section('page_heading')
    Record details
@endsection

@section('content')
    <div class="tenant-page">
        <section class="tenant-hero">
            <div class="tenant-hero__body">
                <div class="tenant-avatar" aria-hidden="true">H</div>
                <div class="tenant-hero__copy">
                    <p class="tenant-kicker">Record</p>
                    <h2 class="tenant-hero__name">Harbor Cafe</h2>
                    <p class="tenant-hero__slug">harbor-cafe</p>
                    <div class="tenant-chips">
                        <span class="tenant-chip"><i class="fas fa-globe"></i> Asia/Dhaka</span>
                        <span class="tenant-chip"><i class="fas fa-coins"></i> BDT</span>
                        <span class="tenant-chip"><i class="fas fa-map-marker-alt"></i> Dhaka</span>
                    </div>
                </div>
                <span class="tenant-status tenant-status--success">Active</span>
            </div>
        </section>

        <div class="row">
            <div class="col-lg-8">
                @include('ui.examples._info_section', [
                    'title' => 'Basic Info',
                    'icon' => 'fas fa-info-circle',
                    'rows' => [
                        'Name' => 'Harbor Cafe',
                        'Slug' => 'harbor-cafe',
                        'City' => 'Dhaka',
                        'Country' => 'Bangladesh',
                    ],
                ])
                @include('ui.examples._info_section', [
                    'title' => 'Contact',
                    'icon' => 'fas fa-user',
                    'rows' => [
                        'Contact person name' => 'Mina Ali',
                        'Contact person email' => 'mina@harbor.test',
                    ],
                ])
            </div>
            <div class="col-lg-4">
                <div class="card admin-surface-card tenant-actions">
                    <div class="card-header admin-surface-card__header">
                        <h3 class="card-title">Actions</h3>
                    </div>
                    <div class="card-body">
                        <a href="#" class="btn admin-btn btn-primary tenant-action-btn">
                            <i class="fas fa-edit"></i> <span>Edit</span>
                        </a>
                        <button type="button" class="btn admin-btn btn-warning tenant-action-btn">
                            <i class="fas fa-ban"></i> <span>Suspend</span>
                        </button>
                        <button type="button" class="btn admin-btn btn-danger tenant-action-btn">
                            <i class="fas fa-trash"></i> <span>Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
