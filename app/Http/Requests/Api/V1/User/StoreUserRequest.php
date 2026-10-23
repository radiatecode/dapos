<?php

namespace App\Http\Requests\Api\V1\User;

use App\DTO\User\CreateUserDTO;
use DA\Admin\Services\FeatureService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

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
            'store_ids' => ['sometimes', 'array'],
            'store_ids.*' => ['integer', 'distinct'],
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
            'store_ids.array' => 'The stores field must be a list.',
            'store_ids.*.integer' => 'The selected store is invalid.',
            'store_ids.*.distinct' => 'The selected store is duplicated.',
        ];
    }

    // public function after(): array
    // {
    //     return [
    //         function (Validator $validator): void {
    //             $featureService = app(FeatureService::class);

    //             if ($featureService->canConsume(auth()->user()->tenant->subscription, 'users', 1)) {
    //                 throw new \Exception('You have reached the maximum number of users for your subscription.');
    //             }

    //             // if ($this->input('from_store_id') !== null && $this->input('from_store_id') === $this->input('to_store_id')) {
    //             //     $validator->errors()->add('to_store_id', 'The destination store must be different from the source store.');
    //             // }
    //         },
    //     ];
    // }

    public function toDTO(): CreateUserDTO
    {
        $data = $this->validated();

        return new CreateUserDTO(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            role_id: $data['role_id'] ?? null,
            storeIds: array_values(array_unique(array_map(intval(...), $data['store_ids'] ?? []))),
        );
    }
}
