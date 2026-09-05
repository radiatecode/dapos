<?php

namespace DA\Admin\Http\Requests\Addon;

use DA\Admin\DTO\AddonDTO;
use DA\Admin\Enums\BillingInterval;
use DA\Admin\Models\AdminUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreAddonRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('addons', 'code')],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_interval' => ['required', Rule::enum(BillingInterval::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The add-on name is required.',
            'code.required' => 'The add-on code is required.',
            'code.unique' => 'This add-on code is already in use.',
            'billing_interval.required' => 'The billing interval is required.',
            'price.required' => 'The add-on price is required.',
            'currency_id.required' => 'The currency is required.',
            'currency_id.exists' => 'The selected currency is invalid.',
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

    public function toDTO(): AddonDTO
    {
        $data = $this->validated();

        return new AddonDTO(
            name: $data['name'],
            code: $data['code'],
            description: is_string($data['description'] ?? null) && $data['description'] !== ''
                ? $data['description']
                : null,
            billing_interval: BillingInterval::from($data['billing_interval']),
            price: number_format((float) $data['price'], 2, '.', ''),
            currency_id: (int) $data['currency_id'],
            is_active: array_key_exists('is_active', $data) ? $this->boolean('is_active') : true,
        );
    }
}
