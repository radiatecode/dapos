<?php

namespace DA\Expense\Http\Requests\Api\V1\ExpenseCategory;

use DA\Expense\DTO\ExpenseCategory\ExpenseCategoryDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseCategoryRequest extends FormRequest
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
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories', 'category_name')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId),
                ),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_name.required' => 'The category name field is required.',
            'category_name.unique' => 'The category name has already been taken.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('category_name') && is_string($this->input('category_name'))) {
            $merge['category_name'] = trim($this->string('category_name')->toString());
        }

        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
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
