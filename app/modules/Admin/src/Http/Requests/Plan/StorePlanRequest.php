<?php

namespace DA\Admin\Http\Requests\Plan;

use DA\Admin\DTO\PlanDTO;
use DA\Admin\DTO\PlanFeatureAssignmentDTO;
use DA\Admin\Enums\BillingInterval;
use DA\Admin\Enums\FeatureType;
use DA\Admin\Models\AdminUser;
use DA\Admin\Models\Feature;
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
            'features.*.assigned' => ['sometimes', 'boolean'],
            'features.*.feature_id' => ['required', 'integer', 'distinct', 'exists:features,id'],
            'features.*.value' => ['nullable', 'string', 'max:50'],
            'features.*.is_unlimited' => ['sometimes', 'boolean'],
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

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('features') || $validator->errors()->has('features.*')) {
                    return;
                }

                /** @var array<int|string, array<string, mixed>> $rows */
                $rows = $this->input('features', []);

                $assignedFeatureIds = [];

                foreach ($rows as $index => $row) {
                    if (! is_array($row) || ! $this->rowIsAssigned($row)) {
                        continue;
                    }

                    $featureId = (int) ($row['feature_id'] ?? 0);
                    $feature = Feature::query()->find($featureId);

                    if (! $feature instanceof Feature) {
                        continue;
                    }

                    $assignedFeatureIds[] = $featureId;

                    $isUnlimited = filter_var($row['is_unlimited'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $value = is_string($row['value'] ?? null) ? trim($row['value']) : '';

                    if ($feature->type === FeatureType::Boolean) {
                        if ($isUnlimited) {
                            $validator->errors()->add(
                                "features.{$index}.is_unlimited",
                                'Boolean features cannot be unlimited.',
                            );
                        }

                        if (! in_array($value, ['0', '1'], true)) {
                            $validator->errors()->add(
                                "features.{$index}.value",
                                'Boolean features must be Yes or No.',
                            );
                        }

                        continue;
                    }

                    if ($isUnlimited) {
                        continue;
                    }

                    if ($value === '' || ! is_numeric($value) || (int) $value < 0) {
                        $validator->errors()->add(
                            "features.{$index}.value",
                            'Limit features require a number, or mark them unlimited.',
                        );
                    }
                }

                if (count($assignedFeatureIds) !== count(array_unique($assignedFeatureIds))) {
                    $validator->errors()->add('features', 'Each feature can only be assigned once.');
                }
            },
        ];
    }

    public function toDTO(): PlanDTO
    {
        $data = $this->validated();

        return new PlanDTO(
            name: $data['name'],
            code: $data['code'],
            description: $this->nullableString($data['description'] ?? null),
            billing_interval: BillingInterval::from($data['billing_interval']),
            price: number_format((float) $data['price'], 2, '.', ''),
            currency_id: (int) $data['currency_id'],
            trial_days: (int) ($data['trial_days'] ?? 0),
            is_active: array_key_exists('is_active', $data) ? $this->boolean('is_active') : true,
            features: $this->featureAssignments($data['features'] ?? []),
        );
    }

    /**
     * @param  array<int|string, mixed>  $features
     * @return list<PlanFeatureAssignmentDTO>
     */
    private function featureAssignments(array $features): array
    {
        $assignments = [];

        foreach ($features as $row) {
            if (! is_array($row) || ! $this->rowIsAssigned($row)) {
                continue;
            }

            $feature = Feature::query()->find((int) $row['feature_id']);

            if (! $feature instanceof Feature) {
                continue;
            }

            $isUnlimited = $feature->isLimit()
                && filter_var($row['is_unlimited'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $value = is_string($row['value'] ?? null) ? trim($row['value']) : null;

            if ($isUnlimited) {
                $value = null;
            } elseif ($feature->isBoolean()) {
                $value = $value === '1' ? '1' : '0';
            }

            $assignments[] = new PlanFeatureAssignmentDTO(
                feature_id: $feature->id,
                value: $value,
                is_unlimited: $isUnlimited,
            );
        }

        return $assignments;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowIsAssigned(array $row): bool
    {
        if (! array_key_exists('assigned', $row)) {
            return true;
        }

        return filter_var($row['assigned'], FILTER_VALIDATE_BOOLEAN);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
