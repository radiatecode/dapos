<?php

namespace DA\Admin\Http\Requests\Tenant;

use DA\Admin\DTO\TenantDTO;
use DA\Admin\Enums\TenantStatus;
use DA\Admin\Models\AdminUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user('admin') instanceof AdminUser) {
            return true;
        }

        return $this->user()?->isPlatformAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')->whereNull('deleted_at')],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['sometimes', 'required', Rule::enum(TenantStatus::class)],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'currency' => ['sometimes', 'required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'address' => ['sometimes', 'array'],
            'address.line_1' => ['nullable', 'string', 'max:255'],
            'address.line_2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['nullable', 'string', 'max:255'],
            'address.state' => ['nullable', 'string', 'max:255'],
            'address.postal_code' => ['nullable', 'string', 'max:50'],
            'address.country' => ['nullable', 'string', 'max:100'],
            'contact' => ['sometimes', 'array'],
            'contact.name' => ['nullable', 'string', 'max:255'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:50'],
            'contact.website' => ['nullable', 'url', 'max:255'],
            'billing' => ['sometimes', 'array'],
            'billing.name' => ['nullable', 'string', 'max:255'],
            'billing.email' => ['nullable', 'email', 'max:255'],
            'billing.phone' => ['nullable', 'string', 'max:50'],
            'billing.address' => ['sometimes', 'array'],
            'billing.address.line_1' => ['nullable', 'string', 'max:255'],
            'billing.address.line_2' => ['nullable', 'string', 'max:255'],
            'billing.address.city' => ['nullable', 'string', 'max:255'],
            'billing.address.state' => ['nullable', 'string', 'max:255'],
            'billing.address.postal_code' => ['nullable', 'string', 'max:50'],
            'billing.address.country' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->ajax() || $this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency') && is_string($this->input('currency'))) {
            $this->merge([
                'currency' => strtoupper($this->string('currency')->toString()),
            ]);
        }
    }

    public function toDTO(): TenantDTO
    {
        $data = $this->validated();
        $logo = $this->file('logo');

        return new TenantDTO(
            name: $data['name'],
            timezone: $data['timezone'] ?? 'Asia/Dhaka',
            currency: $data['currency'] ?? 'BDT',
            address_line_1: $this->stringFrom($data, 'address.line_1'),
            city: $this->stringFrom($data, 'address.city'),
            state: $this->stringFrom($data, 'address.state'),
            postal_code: $this->stringFrom($data, 'address.postal_code'),
            country: $this->stringFrom($data, 'address.country'),
            contact_name: $this->stringFrom($data, 'contact.name'),
            contact_email: $this->stringFrom($data, 'contact.email'),
            contact_phone: $this->stringFrom($data, 'contact.phone'),
            billing_name: $this->stringFrom($data, 'billing.name'),
            billing_email: $this->stringFrom($data, 'billing.email'),
            billing_phone: $this->stringFrom($data, 'billing.phone'),
            billing_address_line_1: $this->stringFrom($data, 'billing.address.line_1'),
            billing_address_line_2: $this->stringFrom($data, 'billing.address.line_2'),
            billing_city: $this->stringFrom($data, 'billing.address.city'),
            billing_state: $this->stringFrom($data, 'billing.address.state'),
            billing_postal_code: $this->stringFrom($data, 'billing.address.postal_code'),
            address_line_2: $this->nullableStringFrom($data, 'address.line_2'),
            website: $this->nullableStringFrom($data, 'contact.website'),
            billing_country: $this->nullableStringFrom($data, 'billing.address.country'),
            slug: $this->nullableStringFrom($data, 'slug'),
            logo: $logo instanceof UploadedFile ? $logo : null,
            status: TenantStatus::tryFrom((int) $this->input('status', TenantStatus::Active->value))
                ?? TenantStatus::Active,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function stringFrom(array $data, string $key): string
    {
        $value = data_get($data, $key);

        return is_string($value) ? $value : '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function nullableStringFrom(array $data, string $key): ?string
    {
        $value = data_get($data, $key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
