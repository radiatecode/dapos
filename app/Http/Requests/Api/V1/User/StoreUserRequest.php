<?php

namespace App\Http\Requests\Api\V1\User;

use App\DTO\User\CreateUserDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
        $tenantId = auth()->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'role_id' => ['sometimes', 'integer',
                Rule::exists('roles', 'id')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'password.required' => 'The password field is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role_id.exists' => 'The selected role is invalid.',
        ];
    }

    public function toDTO(): CreateUserDTO
    {
        $data = $this->validated();

        return new CreateUserDTO(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            role_id: $data['role_id'] ?? null,
        );
    }
}
