<?php

namespace DA\Inventory\Http\Requests\Api\V1\Unit;

use DA\Admin\Services\Tenancy\TenantContext;
use DA\Inventory\DTO\Unit\UnitDTO;
use DA\Inventory\Enums\UnitType;
use DA\Inventory\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Unit::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:20'],
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
            'name.required' => 'The name field is required.',
            'short_name.required' => 'The short name field is required.',
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
            name: $data['name'],
            shortName: $data['short_name'],
            code: $data['code'],
            unitType: UnitType::from($data['unit_type']),
            precision: (int) ($data['precision'] ?? 0),
            isActive: $data['is_active'] ?? true,
        );
    }
}
