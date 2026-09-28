<?php

namespace DA\Inventory\Http\Requests\Api\V1\Supplier;

use DA\Inventory\DTO\Supplier\SupplierDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email field must be a valid email address.',
            'phone.required' => 'The phone field is required.',
            'address.required' => 'The address field is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['name', 'email', 'phone', 'city', 'state', 'country', 'postal_code'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $value = trim($this->string($field)->toString());
                $merge[$field] = $value === '' ? null : $value;
            }
        }

        if ($this->has('address') && is_string($this->input('address'))) {
            $merge['address'] = trim($this->string('address')->toString());
        }

        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function toDTO(): SupplierDTO
    {
        $data = $this->validated();

        return new SupplierDTO(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'],
            address: $data['address'],
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }
}
