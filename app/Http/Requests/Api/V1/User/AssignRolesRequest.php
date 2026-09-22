<?php

namespace App\Http\Requests\Api\V1\User;

use App\DTO\User\AssignRolesDTO;
use App\Models\User;
use DA\Admin\Services\Tenancy\TenantContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('assignRoles', $user) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->id();

        return [
            'role_ids' => ['required', 'array'],
            'role_ids.*' => [
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role_ids.required' => 'The role ids field is required.',
            'role_ids.*.exists' => 'The selected role is invalid.',
        ];
    }

    public function toDTO(): AssignRolesDTO
    {
        return new AssignRolesDTO(
            role_ids: array_map('intval', $this->validated('role_ids')),
        );
    }
}
