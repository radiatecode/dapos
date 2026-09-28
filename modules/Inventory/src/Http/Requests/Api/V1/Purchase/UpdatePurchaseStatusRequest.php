<?php

namespace DA\Inventory\Http\Requests\Api\V1\Purchase;

use DA\Inventory\Enums\PurchaseStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(PurchaseStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'The status field is required.',
            'status.enum' => 'The selected status is invalid.',
        ];
    }

    public function status(): PurchaseStatus
    {
        return PurchaseStatus::from($this->validated('status'));
    }
}
