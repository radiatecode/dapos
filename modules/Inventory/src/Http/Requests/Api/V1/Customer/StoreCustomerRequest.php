<?php

namespace DA\Inventory\Http\Requests\Api\V1\Customer;

use DA\Inventory\DTO\Customer\CustomerDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'phone.required' => 'The phone field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email field must be a valid email address.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['name', 'phone', 'email'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $value = trim($this->string($field)->toString());
                $merge[$field] = $value === '' ? null : $value;
            }
        }

        if ($this->has('address') && is_string($this->input('address'))) {
            $value = trim($this->string('address')->toString());
            $merge['address'] = $value === '' ? null : $value;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function toDTO(): CustomerDTO
    {
        $data = $this->validated();

        return new CustomerDTO(
            name: $data['name'],
            phone: $data['phone'],
            email: $data['email'],
            address: $data['address'] ?? null,
        );
    }
}
