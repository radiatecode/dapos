@extends('admin::app.layouts.app')

@section('title', 'Create tenant')

@section('page_heading')
    <i class="fas fa-building"></i> Create tenant
@endsection

@section('content')
    <form method="POST" id="create-tenant-form" action="{{ route('admin.tenants.store') }}" enctype="multipart/form-data"
        data-parsley-validate="" autocomplete="off">
        @csrf

        <div class="row">
            <div class="col-12">
                <x-admin::boot-card title="Create tenant">
                    <ul class="nav nav-tabs" id="create-tenant-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab"
                                data-bs-target="#basic-info" type="button" role="tab" aria-controls="basic-info"
                                aria-selected="true">
                                <i class="fas fa-info-circle"></i> Basic Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact"
                                type="button" role="tab" aria-controls="contact" aria-selected="false">
                                <i class="fas fa-user"></i> Contact
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="billing-info-tab" data-bs-toggle="tab"
                                data-bs-target="#billing-info" type="button" role="tab" aria-controls="billing-info"
                                aria-selected="false">
                                <i class="fas fa-file-invoice-dollar"></i> Billing Info
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="create-tenant-tabs-content">
                        <div class="tab-pane fade show active" id="basic-info" role="tabpanel"
                            aria-labelledby="basic-info-tab" tabindex="0">
                            <div class="row">
                                <div class="col-md-6">
                                    <x-admin::form.input name="name" label="Name" label-icon="fas fa-building"
                                        required />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="logo" label="Logo" label-icon="fas fa-image" type="file"
                                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                        help-block="JPG, PNG, or WebP. Max 2 MB." />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.select2 name="timezone" label="Timezone" label-icon="fas fa-globe"
                                        selected="{{ old('timezone', 'Asia/Dhaka') }}">
                                        @foreach ($timezones as $timezone)
                                            <option value="{{ $timezone }}">{{ $timezone }}</option>
                                        @endforeach
                                    </x-admin::form.select2>
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.select2 name="currency" label="Currency" label-icon="fas fa-coins"
                                        selected="{{ old('currency', 'BDT') }}">
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency }}">{{ $currency }}</option>
                                        @endforeach
                                    </x-admin::form.select2>
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="address[line_1]" id="address_line_1"
                                        label="Address line 1" label-icon="fas fa-map-marker-alt" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="address[line_2]" id="address_line_2"
                                        label="Address line 2" label-icon="fas fa-map-marker-alt" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="address[city]" id="address_city" label="City"
                                        label-icon="fas fa-city" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="address[state]" id="address_state" label="State"
                                        label-icon="fas fa-map" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="address[postal_code]" id="address_postal_code"
                                        label="Postal code" label-icon="fas fa-mail-bulk" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="address[country]" id="address_country" label="Country"
                                        label-icon="fas fa-flag" />
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab"
                            tabindex="0">
                            <div class="row">
                                <div class="col-md-6">
                                    <x-admin::form.input name="contact[name]" id="contact_person_name"
                                        label="Contact person name" label-icon="fas fa-user" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="contact[email]" id="contact_person_email"
                                        label="Contact person email" label-icon="fas fa-envelope" type="email" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="contact[phone]" id="contact_person_phone"
                                        label="Contact person phone" label-icon="fas fa-phone" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="contact[website]" id="website" label="Website"
                                        label-icon="fas fa-link" type="url" placeholder="https://" />
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="billing-info" role="tabpanel" aria-labelledby="billing-info-tab"
                            tabindex="0">
                            <div class="row">
                                <div class="col-md-6">
                                    <x-admin::form.input name="billing[name]" id="billing_name" label="Billing name"
                                        label-icon="fas fa-user-tie" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="billing[email]" id="billing_email" label="Billing email"
                                        label-icon="fas fa-envelope" type="email" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="billing[phone]" id="billing_phone" label="Billing phone"
                                        label-icon="fas fa-phone" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="billing[address][line_1]" id="billing_address_line_1"
                                        label="Billing address line 1" label-icon="fas fa-map-marker-alt" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="billing[address][line_2]" id="billing_address_line_2"
                                        label="Billing address line 2" label-icon="fas fa-map-marker-alt" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="billing[address][city]" id="billing_city"
                                        label="Billing city" label-icon="fas fa-city" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="billing[address][state]" id="billing_state"
                                        label="Billing state" label-icon="fas fa-map" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="billing[address][postal_code]"
                                        id="billing_postal_code" label="Billing postal code"
                                        label-icon="fas fa-mail-bulk" />
                                </div>
                                <div class="col-md-6">
                                    <x-admin::form.input name="billing[address][country]" id="billing_country"
                                        label="Billing country" label-icon="fas fa-flag" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-slot:footer>
                        <x-admin::button type="submit" class="btn-primary" text="Create" icon="fas fa-save" />
                    </x-slot:footer>
                </x-admin::boot-card>
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script src="{{ asset('vendor/admin/js/submit.on.validated.js') }}"></script>
    <script>
        $.ajaxSubmitOnValidated('create-tenant-form', {
            loader: 'btn-loader',
            errorType: 'toast',
            redirectTo: function() {
                window.location.href = @json(route('admin.tenants.index'));
            },
        });
    </script>
@endpush
