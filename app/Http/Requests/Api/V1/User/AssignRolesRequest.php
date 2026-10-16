<?php

namespace App\Http\Requests\Api\V1\User;

use App\DTO\User\AssignRolesDTO;
use DA\Admin\Services\Tenancy\TenantContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->id();

        return [
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role_id.required' => 'The role id field is required.',
            'role_id.exists' => 'The selected role is invalid.',
        ];
    }

    public function toDTO(): AssignRolesDTO
    {
        return new AssignRolesDTO(
            role_id: $this->validated('role_id'),
        );
    }
}
