<?php

namespace DA\Inventory\Http\Requests\Api\V1\PurchaseReturn;

use DA\Inventory\Enums\PurchaseReturnStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseReturnStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in([
                PurchaseReturnStatus::Approved->value,
                PurchaseReturnStatus::Reject->value,
            ])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'The status field is required.',
            'status.in' => 'The selected status is invalid.',
        ];
    }

    public function status(): PurchaseReturnStatus
    {
        return PurchaseReturnStatus::from($this->validated('status'));
    }
}
