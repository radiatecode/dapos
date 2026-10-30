<?php

namespace DA\Expense\Actions\Expense;

use DA\Expense\DTO\Expense\ExpenseDTO;
use DA\Expense\Enums\ExpenseStatus;
use DA\Expense\Models\Expense;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateExpense
{
    public function handle(int $id, ExpenseDTO $dto): Expense
    {
        $expense = Expense::queries()->findForTenant($id);

        if (! $expense->authorizeAction($expense, 'update')) {
            throw ValidationException::withMessages([
                'expense' => 'You are not authorized to update this expense.',
            ]);
        }

        if ($expense->status !== ExpenseStatus::Pending) {
            throw ValidationException::withMessages([
                'expense' => 'Only a pending expense can be updated.',
            ]);
        }

        $expense->category_id = $dto->categoryId;
        $expense->store_id = $dto->storeId;
        $expense->description = $dto->description;
        $expense->amount = bcadd($dto->amount, '0', 4);
        $expense->expense_date = $dto->expenseDate;
        $expense->updated_by = auth()->id();

        if ($dto->attachment !== null) {
            if (is_string($expense->attachment) && $expense->attachment !== '') {
                Storage::disk('public')->delete($expense->attachment);
            }

            $expense->attachment = $dto->attachment->store('expenses', 'public');
        }

        $expense->save();

        return $expense->refresh()->load(['category', 'store']);
    }
}
