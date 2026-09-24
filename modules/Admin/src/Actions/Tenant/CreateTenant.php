<?php

namespace DA\Admin\Actions\Tenant;

use DA\Admin\DTO\TenantDTO;
use DA\Admin\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CreateTenant
{
    public function handle(TenantDTO $tenantDTO): Tenant
    {
        $tenant = new Tenant;

        $tenant->name = $tenantDTO->name;
        $tenant->slug = $tenantDTO->slug ?: $this->uniqueSlug($tenantDTO->name);
        $tenant->timezone = $tenantDTO->timezone;
        $tenant->currency = $tenantDTO->currency;
        $tenant->address_line_1 = $tenantDTO->address_line_1;
        $tenant->address_line_2 = $tenantDTO->address_line_2;
        $tenant->city = $tenantDTO->city;
        $tenant->state = $tenantDTO->state;
        $tenant->postal_code = $tenantDTO->postal_code;
        $tenant->country = $tenantDTO->country;
        $tenant->contact_person_name = $tenantDTO->contact_name;
        $tenant->contact_person_email = $tenantDTO->contact_email;
        $tenant->contact_person_phone = $tenantDTO->contact_phone;
        $tenant->website = $tenantDTO->website;
        $tenant->billing_name = $tenantDTO->billing_name;
        $tenant->billing_email = $tenantDTO->billing_email;
        $tenant->billing_phone = $tenantDTO->billing_phone;
        $tenant->billing_address_line_1 = $tenantDTO->billing_address_line_1;
        $tenant->billing_address_line_2 = $tenantDTO->billing_address_line_2;
        $tenant->billing_city = $tenantDTO->billing_city;
        $tenant->billing_state = $tenantDTO->billing_state;
        $tenant->billing_postal_code = $tenantDTO->billing_postal_code;
        $tenant->billing_country = $tenantDTO->billing_country;
        $tenant->status = $tenantDTO->status;

        if ($tenantDTO->logo instanceof UploadedFile) {
            $tenant->logo = $tenantDTO->logo->store('tenants/logos', 'public');
        }

        $tenant->save();

        return $tenant;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $suffix = 1;

        while (Tenant::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
