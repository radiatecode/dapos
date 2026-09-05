<?php

namespace DA\Admin\Http\Requests\Feature;

use DA\Admin\Models\Feature;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateFeatureRequest extends StoreFeatureRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Feature|null $feature */
        $feature = $this->route('feature');

        return [
            ...parent::rules(),
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('features', 'code')->ignore($feature)],
        ];
    }
}
