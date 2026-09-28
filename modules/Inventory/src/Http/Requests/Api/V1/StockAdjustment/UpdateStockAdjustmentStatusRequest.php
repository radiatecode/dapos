<?php

namespace DA\Inventory\Http\Requests\Api\V1\StockAdjustment;

use DA\Inventory\Enums\StockAdjustmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockAdjustmentStatusRequest extends FormRequest
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
                StockAdjustmentStatus::Completed->value,
                StockAdjustmentStatus::Cancelled->value,
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

    public function status(): StockAdjustmentStatus
    {
        return StockAdjustmentStatus::from($this->validated('status'));
    }
}
