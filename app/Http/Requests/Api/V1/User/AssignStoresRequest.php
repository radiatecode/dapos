<?php

namespace App\Http\Requests\Api\V1\User;

use App\DTO\User\AssignStoresDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignStoresRequest extends FormRequest
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
        return [
            'store_ids' => ['present', 'array'],
            'store_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_ids.present' => 'The stores field is required.',
            'store_ids.array' => 'The stores field must be a list.',
            'store_ids.*.integer' => 'The selected store is invalid.',
            'store_ids.*.distinct' => 'The selected store is duplicated.',
        ];
    }

    public function toDTO(): AssignStoresDTO
    {
        /** @var list<int|string> $storeIds */
        $storeIds = $this->validated('store_ids');

        return new AssignStoresDTO(
            storeIds: array_values(array_unique(array_map(intval(...), $storeIds))),
        );
    }
}
