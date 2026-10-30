<?php

namespace DA\Expense\Http\Requests\Api\V1\Expense;

use DA\Expense\Enums\ExpenseStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseStatusRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::in([
                    ExpenseStatus::Approved->value,
                    ExpenseStatus::Rejected->value,
                ]),
            ],
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

    public function status(): ExpenseStatus
    {
        return ExpenseStatus::from($this->validated('status'));
    }
}
