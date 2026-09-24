<?php

namespace DA\Admin\Http\Requests\Feature;

use DA\Admin\DTO\FeatureDTO;
use DA\Admin\Enums\FeatureType;
use DA\Admin\Models\AdminUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') instanceof AdminUser;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('features', 'code')],
            'type' => ['required', Rule::enum(FeatureType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The feature name is required.',
            'code.required' => 'The feature code is required.',
            'code.unique' => 'This feature code is already in use.',
            'type.required' => 'The feature type is required.',
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
        if ($this->has('code') && is_string($this->input('code'))) {
            $this->merge([
                'code' => $this->string('code')->trim()->lower()->toString(),
            ]);
        }
    }

    public function toDTO(): FeatureDTO
    {
        $data = $this->validated();

        return new FeatureDTO(
            name: $data['name'],
            code: $data['code'],
            type: FeatureType::from($data['type']),
            description: is_string($data['description'] ?? null) && $data['description'] !== ''
                ? $data['description']
                : null,
        );
    }
}
