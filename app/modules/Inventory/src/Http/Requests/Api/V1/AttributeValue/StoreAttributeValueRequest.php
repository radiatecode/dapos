<?php

namespace DA\Inventory\Http\Requests\Api\V1\AttributeValue;

use DA\Inventory\DTO\AttributeValue\AttributeValueDTO;
use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AttributeValue::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $attribute = $this->route('attribute');
        $attributeId = $attribute instanceof Attribute ? $attribute->id : 0;

        return [
            'value' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('attribute_values', 'slug')->where(fn ($query) => $query->where('attribute_id', $attributeId)),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::when($this->filled('code'), [
                    Rule::unique('attribute_values', 'code')->where(fn ($query) => $query->where('attribute_id', $attributeId)),
                ]),
            ],
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
            'value.required' => 'The value field is required.',
            'slug.unique' => 'The slug has already been taken.',
            'code.unique' => 'The code has already been taken.',
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

    public function toDTO(): AttributeValueDTO
    {
        $data = $this->validated();

        return new AttributeValueDTO(
            value: $data['value'],
            slug: isset($data['slug']) && $data['slug'] !== '' ? $data['slug'] : null,
            code: $data['code'] ?? null,
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isActive: $data['is_active'] ?? true,
        );
    }
}
