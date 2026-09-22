<?php

namespace App\Http\Requests\Api\V1\User;

use App\DTO\User\UpdatePasswordDTO;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('updatePassword', $user) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'current_password' => [
                Rule::requiredIf(fn (): bool => $this->user()?->is($user) ?? false),
                'string',
                'current_password',
            ],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'The current password field is required.',
            'current_password.current_password' => 'The current password is incorrect.',
            'password.required' => 'The password field is required.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }

    public function toDTO(): UpdatePasswordDTO
    {
        return new UpdatePasswordDTO(
            password: $this->validated('password'),
        );
    }
}
