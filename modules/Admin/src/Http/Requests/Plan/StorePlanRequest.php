<?php

namespace DA\Admin\Http\Requests\Plan;

use DA\Admin\DTO\PlanDTO;
use DA\Admin\Enums\BillingInterval;
use DA\Admin\Models\AdminUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('plans', 'code')],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_interval' => ['required', Rule::enum(BillingInterval::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],
            'features' => ['nullable', 'array'],
            'limit_features' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The plan name is required.',
            'code.required' => 'The plan code is required.',
            'code.unique' => 'This plan code is already in use.',
            'billing_interval.required' => 'The billing interval is required.',
            'price.required' => 'The plan price is required.',
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

    public function toDTO(): PlanDTO
    {
        $data = $this->validated();

        return new PlanDTO(
            name: $data['name'],
            code: $data['code'],
            description: $data['description'] ?? null,
            billing_interval: BillingInterval::from($data['billing_interval']),
            price: number_format((float) $data['price'], 2, '.', ''),
            currency_id: (int) $data['currency_id'],
            trial_days: (int) ($data['trial_days'] ?? 0),
            is_active: array_key_exists('is_active', $data) ? $this->boolean('is_active') : true,
            features: [
                'module' => $data['features'] ?? [],
                'limit' => $this->featureLimitAssignments($data['limit_features'] ?? []),
            ],
        );
    }

    private function featureLimitAssignments(array $features): array
    {
        $assignments = [];

        foreach ($features as $featureCode => $featureConfig) {
            if (! is_array($featureConfig)) {
                continue;
            }

            $isUnlimited = isset($featureConfig['unlimited']) ? true : false;

            $assignments[] = [
                'feature' => $featureCode,
                'value' => $isUnlimited ? null : ($featureConfig['value'] ?? null),
                'is_unlimited' => $isUnlimited,
            ];
        }

        return $assignments;
    }
}
