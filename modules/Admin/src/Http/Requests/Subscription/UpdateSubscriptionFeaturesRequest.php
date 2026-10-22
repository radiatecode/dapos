<?php

namespace DA\Admin\Http\Requests\Subscription;

use DA\Admin\Models\AdminUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSubscriptionFeaturesRequest extends FormRequest
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
            'features' => ['required', 'array'],
            'limit_features' => ['required', 'array'],
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

    public function toDTO(): array
    {
        return [
            'module' => $this->features ?? [],
            'limit' => $this->featureLimitAssignments($this->limit_features),
        ];
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
