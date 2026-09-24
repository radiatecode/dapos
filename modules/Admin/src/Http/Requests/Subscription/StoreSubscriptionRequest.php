<?php

namespace DA\Admin\Http\Requests\Subscription;

use DA\Admin\DTO\CreateSubscriptionDTO;
use DA\Admin\Models\AdminUser;
use DA\Admin\Models\Subscription;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSubscriptionRequest extends FormRequest
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
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'start_trial' => ['sometimes', 'boolean'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer', 'distinct', 'exists:addons,id'],
            'grace_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_id.required' => 'The tenant is required.',
            'tenant_id.exists' => 'The selected tenant is invalid.',
            'plan_id.required' => 'The plan is required.',
            'plan_id.exists' => 'The selected plan is invalid.',
            'addon_ids.*.exists' => 'One or more selected add-ons are invalid.',
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

    public function toDTO(): CreateSubscriptionDTO
    {
        $data = $this->validated();

        /** @var list<int> $addonIds */
        $addonIds = array_map(intval(...), $data['addon_ids'] ?? []);

        return new CreateSubscriptionDTO(
            tenant_id: (int) $data['tenant_id'],
            plan_id: (int) $data['plan_id'],
            start_trial: $this->boolean('start_trial'),
            addon_ids: $addonIds,
            grace_days: array_key_exists('grace_days', $data)
                ? (int) $data['grace_days']
                : Subscription::DEFAULT_GRACE_DAYS,
        );
    }
}
