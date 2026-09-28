<?php

namespace DA\Inventory\Http\Requests\Api\V1\StockTransfer;

use DA\Inventory\Enums\StockTransferStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockTransferStatusRequest extends FormRequest
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
                StockTransferStatus::Pending->value,
                StockTransferStatus::Completed->value,
                StockTransferStatus::Cancelled->value,
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

    public function status(): StockTransferStatus
    {
        return StockTransferStatus::from($this->validated('status'));
    }
}
