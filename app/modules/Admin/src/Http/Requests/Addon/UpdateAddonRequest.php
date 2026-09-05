<?php

namespace DA\Admin\Http\Requests\Addon;

use DA\Admin\Models\Addon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateAddonRequest extends StoreAddonRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Addon|null $addon */
        $addon = $this->route('addon');

        return [
            ...parent::rules(),
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('addons', 'code')->ignore($addon)],
        ];
    }
}
