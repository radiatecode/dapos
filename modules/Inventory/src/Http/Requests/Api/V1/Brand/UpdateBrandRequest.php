<?php

namespace DA\Inventory\Http\Requests\Api\V1\Brand;

use DA\Inventory\DTO\Brand\BrandDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
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
        $brandId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('brands', 'slug')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($brandId),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::when($this->filled('code'), [
                    Rule::unique('brands', 'code')
                        ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                        ->ignore($brandId),
                ]),
            ],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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
            'logo.image' => 'The logo must be an image.',
            'logo.mimes' => 'The logo must be a file of type: jpg, jpeg, png, webp.',
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

    public function toDTO(): BrandDTO
    {
        $data = $this->validated();
        $logo = $this->file('logo');

        return new BrandDTO(
            name: $data['name'],
            slug: isset($data['slug']) && $data['slug'] !== '' ? $data['slug'] : null,
            code: $data['code'] ?? null,
            description: $data['description'] ?? null,
            logo: $logo instanceof UploadedFile ? $logo : null,
            isActive: $data['is_active'] ?? true,
        );
    }
}
