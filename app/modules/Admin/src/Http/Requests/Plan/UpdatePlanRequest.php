<?php

namespace DA\Admin\Http\Requests\Plan;

use DA\Admin\Models\Plan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends StorePlanRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Plan|null $plan */
        $plan = $this->route('plan');

        return [
            ...parent::rules(),
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('plans', 'code')->ignore($plan)],
        ];
    }
}
