<?php

namespace App\Http\Requests\Api\V1\User;

use App\DTO\User\UpdatePasswordDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
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
        $id = $this->route('id');

        return [
            'current_password' => [
                Rule::requiredIf(fn (): bool => $this->user()?->id === $id),
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
