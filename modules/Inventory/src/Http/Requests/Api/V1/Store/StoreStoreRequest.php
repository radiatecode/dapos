<?php

namespace DA\Inventory\Http\Requests\Api\V1\Store;

use DA\Inventory\DTO\Store\StoreDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStoreRequest extends FormRequest
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
            'address' => ['required', 'string'],
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
            'address.required' => 'The address field is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('name') && is_string($this->input('name'))) {
            $merge['name'] = trim($this->string('name')->toString());
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

    public function toDTO(): StoreDTO
    {
        $data = $this->validated();

        return new StoreDTO(
            name: $data['name'],
            address: $data['address'],
            isActive: $data['is_active'] ?? true,
        );
    }
}
