<ul class="nav nav-tabs tenant-tabs" id="record-form-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info"
            type="button" role="tab">
            <i class="fas fa-info-circle"></i> Basic Info
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button"
            role="tab">
            <i class="fas fa-user"></i> Contact
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="billing-info-tab" data-bs-toggle="tab" data-bs-target="#billing-info"
            type="button" role="tab">
            <i class="fas fa-file-invoice-dollar"></i> Billing Info
        </button>
    </li>
</ul>

<div class="tab-content pt-3">
    <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <x-ui::form.input name="name" label="Name" label-icon="fas fa-building" required />
            </div>
            <div class="col-md-6">
                <x-ui::form.input name="logo" label="Logo" label-icon="fas fa-image" type="file"
                    help-block="JPG, PNG, or WebP. Max 2 MB." />
            </div>
            <div class="col-md-6">
                <x-ui::form.select2 name="timezone" label="Timezone" label-icon="fas fa-globe" selected="Asia/Dhaka">
                    <option value="Asia/Dhaka">Asia/Dhaka</option>
                    <option value="UTC">UTC</option>
                </x-ui::form.select2>
            </div>
            <div class="col-md-6">
                <x-ui::form.input name="city" label="City" label-icon="fas fa-city" />
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="contact" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <x-ui::form.input name="contact[name]" label="Contact person name" label-icon="fas fa-user" />
            </div>
            <div class="col-md-6">
                <x-ui::form.input name="contact[email]" label="Contact person email" type="email"
                    label-icon="fas fa-envelope" />
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="billing-info" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <x-ui::form.input name="billing[name]" label="Billing name" label-icon="fas fa-user-tie" />
            </div>
            <div class="col-md-6">
                <x-ui::form.input name="billing[email]" label="Billing email" type="email"
                    label-icon="fas fa-envelope" />
            </div>
        </div>
    </div>
</div>
