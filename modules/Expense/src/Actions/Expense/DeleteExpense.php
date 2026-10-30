<?php

namespace DA\Expense\Actions\Expense;

use DA\Expense\Enums\ExpenseStatus;
use DA\Expense\Models\Expense;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteExpense
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $expense = Expense::queries()->findForTenant((int) $id);

            if (! $expense->authorizeAction($expense, 'delete')) {
                throw ValidationException::withMessages([
                    'expense' => 'You are not authorized to delete this expense.',
                ]);
            }

            if ($expense->status !== ExpenseStatus::Pending) {
                throw ValidationException::withMessages([
                    'ids' => 'Only a pending expense can be deleted.',
                ]);
            }

            if (is_string($expense->attachment) && $expense->attachment !== '') {
                Storage::disk('public')->delete($expense->attachment);
            }

            $expense->delete();
        }
    }
}
