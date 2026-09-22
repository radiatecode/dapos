<?php

namespace DA\Inventory\Http\Requests\Api\V1\Attribute;

use DA\Admin\Services\Tenancy\TenantContext;
use DA\Inventory\DTO\Attribute\AttributeDTO;
use DA\Inventory\Enums\AttributeInputType;
use DA\Inventory\Models\Attribute;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attribute = $this->route('attribute');

        return $attribute instanceof Attribute && ($this->user()?->can('update', $attribute) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->id();
        $attribute = $this->route('attribute');
        $attributeId = $attribute instanceof Attribute ? $attribute->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('attributes', 'slug')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($attributeId),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::when($this->filled('code'), [
                    Rule::unique('attributes', 'code')
                        ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                        ->ignore($attributeId),
                ]),
            ],
            'input_type' => ['required', Rule::enum(AttributeInputType::class)],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
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
            'slug.unique' => 'The slug has already been taken.',
            'code.unique' => 'The code has already been taken.',
            'input_type.required' => 'The input type field is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('slug') && is_string($this->input('slug'))) {
            $merge['slug'] = Str::slug($this->string('slug')->toString()) ?: null;
        }

        if ($this->has('code') && is_string($this->input('code'))) {
            $code = trim($this->string('code')->toString());
            $merge['code'] = $code === '' ? null : $code;
        }

        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function toDTO(): AttributeDTO
    {
        $data = $this->validated();

        return new AttributeDTO(
            name: $data['name'],
            slug: isset($data['slug']) && $data['slug'] !== '' ? $data['slug'] : null,
            code: $data['code'] ?? null,
            inputType: AttributeInputType::from($data['input_type']),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isActive: $data['is_active'] ?? true,
        );
    }
}
