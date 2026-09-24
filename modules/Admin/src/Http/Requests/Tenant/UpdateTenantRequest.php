<?php

namespace DA\Admin\Http\Requests\Tenant;

use DA\Admin\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends StoreTenantRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Tenant|null $tenant */
        $tenant = $this->route('tenant');

        return [
            ...parent::rules(),
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')
                ->ignore($tenant)->whereNull('deleted_at')],
        ];
    }
}
