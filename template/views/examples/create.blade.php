@extends('ui.layouts.app')

@section('title', 'Create record')

@section('page_heading')
    Create record
@endsection

@section('content')
    <div class="admin-form-page">
        <form method="POST" action="#" novalidate data-parsley-validate="" autocomplete="off">
            @csrf

            <x-ui::boot-card title="Create record">
                <x-slot:header>
                    <p class="admin-card-hint mb-0">Add the profile, then contact and billing details.</p>
                </x-slot:header>

                @include('ui.examples._fields')

                <x-slot:footer>
                    <a href="#" class="btn admin-btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <x-ui::button type="submit" class="btn-primary" text="Create" icon="fas fa-save" />
                </x-slot:footer>
            </x-ui::boot-card>
        </form>
    </div>
@endsection
