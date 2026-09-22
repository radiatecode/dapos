<?php

namespace App\Http\Requests\Api\V1\Role;

use App\DTO\Role\RoleDTO;
use App\Enums\Permission;
use App\Models\Role;
use DA\Admin\Services\Tenancy\TenantContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Role::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('roles', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'permissions' => ['required', 'array'],
            'permissions.*' => [Rule::enum(Permission::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'slug.unique' => 'The slug has already been taken.',
            'permissions.required' => 'The permissions field is required.',
            'permissions.*.enum' => 'The selected permission is invalid.',
            'permissions.*.Illuminate\Validation\Rules\Enum' => 'The selected permission is invalid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug') && is_string($this->input('slug'))) {
            $this->merge([
                'slug' => Str::slug($this->string('slug')->toString()) ?: null,
            ]);
        }
    }

    public function toDTO(): RoleDTO
    {
        $data = $this->validated();

        return new RoleDTO(
            name: $data['name'],
            slug: isset($data['slug']) && $data['slug'] !== '' ? $data['slug'] : null,
            permissions: array_values($data['permissions']),
        );
    }
}
