<?php

namespace DA\Expense\Actions\ExpenseCategory;

use DA\Expense\DTO\ExpenseCategory\ExpenseCategoryDTO;
use DA\Expense\Models\ExpenseCategory;

class CreateExpenseCategory
{
    public function handle(ExpenseCategoryDTO $dto): ExpenseCategory
    {
        $userId = auth()->id();

        $category = new ExpenseCategory;
        $category->tenant_id = auth()->user()->tenant_id;
        $category->category_name = $dto->categoryName;
        $category->is_active = $dto->isActive;
        $category->created_by = $userId;
        $category->updated_by = $userId;
        $category->save();

        return $category;
    }
}
