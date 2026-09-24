<?php

namespace DA\Inventory\Http\Requests\Api\V1\Unit;

use DA\Inventory\DTO\Unit\UnitDTO;
use DA\Inventory\Enums\UnitType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
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
        $tenantId = auth()->user()->tenant_id;

        return [
            'unit_name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'code')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'unit_type' => ['required', Rule::enum(UnitType::class)],
            'precision' => ['sometimes', 'integer', 'min:0', 'max:6'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'unit_name.required' => 'The unit name field is required.',
            'code.required' => 'The code field is required.',
            'code.unique' => 'The code has already been taken.',
            'unit_type.required' => 'The unit type field is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('code') && is_string($this->input('code'))) {
            $merge['code'] = Str::upper(trim($this->string('code')->toString()));
        }

        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function toDTO(): UnitDTO
    {
        $data = $this->validated();

        return new UnitDTO(
            name: $data['unit_name'],
            code: $data['code'],
            unitType: UnitType::from($data['unit_type']),
            precision: (int) ($data['precision'] ?? 0),
            isActive: $data['is_active'] ?? true,
        );
    }
}
