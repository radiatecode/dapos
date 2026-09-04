<?php

namespace DA\Admin\Http\Requests\Tenant;

use DA\Admin\Enums\TenantStatus;
use DA\Admin\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Tenant|null $tenant */
        $tenant = $this->route('tenant');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant)],
            'legal_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['sometimes', 'required', Rule::enum(TenantStatus::class)],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'currency' => ['sometimes', 'required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'address' => ['sometimes', 'array'],
            'address.line_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.postal_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address.country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contact' => ['sometimes', 'array'],
            'contact.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact.email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact.phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact.website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'billing' => ['sometimes', 'array'],
            'billing.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing.email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'billing.phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'billing.tax_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            'billing.address' => ['sometimes', 'array'],
            'billing.address.line_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing.address.line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing.address.city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing.address.state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing.address.postal_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'billing.address.country' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency') && is_string($this->input('currency'))) {
            $this->merge([
                'currency' => strtoupper($this->string('currency')->toString()),
            ]);
        }
    }
}
