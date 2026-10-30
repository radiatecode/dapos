<?php

namespace DA\Expense\Actions\ExpenseCategory;

use DA\Expense\DTO\ExpenseCategory\ExpenseCategoryDTO;
use DA\Expense\Models\ExpenseCategory;
use Illuminate\Validation\ValidationException;

class UpdateExpenseCategory
{
    public function handle(int $id, ExpenseCategoryDTO $dto): ExpenseCategory
    {
        $category = ExpenseCategory::queries()->findForTenant($id);

        if (! $category->authorizeAction($category, 'update')) {
            throw ValidationException::withMessages([
                'expense_category' => 'You are not authorized to update this expense category.',
            ]);
        }

        $category->category_name = $dto->categoryName;
        $category->is_active = $dto->isActive;
        $category->updated_by = auth()->id();
        $category->save();

        return $category->refresh();
    }
}
