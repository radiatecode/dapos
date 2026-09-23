<?php

namespace DA\Inventory\Http\Requests\Api\V1\Category;

use DA\Inventory\DTO\Category\CategoryDTO;
use DA\Inventory\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category && ($this->user()?->can('update', $category) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $category = $this->route('category');

        $categoryId = $category instanceof Category ? $category->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('categories', 'slug')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($categoryId),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::when($this->filled('code'), [
                    Rule::unique('categories', 'code')
                        ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                        ->ignore($categoryId),
                ]),
            ],
            'description' => ['nullable', 'string'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::notIn([$categoryId]),
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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
            'parent_id.exists' => 'The selected parent category is invalid.',
            'parent_id.not_in' => 'A category cannot be its own parent.',
            'image.image' => 'The image must be an image.',
            'image.mimes' => 'The image must be a file of type: jpg, jpeg, png, webp.',
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('parent_id')) {
                    return;
                }

                $category = $this->route('category');
                $parentId = $this->input('parent_id');

                if (! $category instanceof Category || $parentId === null || $parentId === '') {
                    return;
                }

                $parentId = (int) $parentId;

                if (in_array($parentId, Category::queries()->descendantIds($category->id), true)) {
                    $validator->errors()->add('parent_id', 'A category cannot be nested under one of its descendants.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge['slug'] = Str::slug($this->input('name'));

        if ($this->has('code') && is_string($this->input('code'))) {
            $code = trim($this->string('code')->toString());
            $merge['code'] = $code === '' ? null : $code;
        }

        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }

        $this->merge($merge);
    }

    public function toDTO(): CategoryDTO
    {
        $data = $this->validated();
        $image = $this->file('image');

        return new CategoryDTO(
            name: $data['name'],
            slug: isset($data['slug']) && $data['slug'] !== '' ? $data['slug'] : null,
            code: $data['code'] ?? null,
            description: $data['description'] ?? null,
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            image: $image instanceof UploadedFile ? $image : null,
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isActive: $data['is_active'] ?? true,
        );
    }
}
