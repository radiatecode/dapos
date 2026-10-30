<?php

namespace DA\Expense\Http\Requests\Api\V1\ExpenseCategory;

use DA\Expense\DTO\ExpenseCategory\ExpenseCategoryDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends StoreExpenseCategoryRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories', 'category_name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($this->route('id')),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function toDTO(): ExpenseCategoryDTO
    {
        $data = $this->validated();

        return new ExpenseCategoryDTO(
            categoryName: $data['category_name'],
            isActive: $data['is_active'] ?? true,
        );
    }
}
