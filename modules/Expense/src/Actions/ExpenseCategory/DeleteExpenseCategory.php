<?php

namespace DA\Expense\Actions\ExpenseCategory;

use DA\Expense\Models\Expense;
use DA\Expense\Models\ExpenseCategory;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class DeleteExpenseCategory
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $category = ExpenseCategory::queries()->findForTenant((int) $id);

            if (! $category->authorizeAction($category, 'delete')) {
                throw ValidationException::withMessages([
                    'expense_category' => 'You are not authorized to delete this expense category.',
                ]);
            }

            if (Expense::queries()->existsForCategory($category->id)) {
                throw ValidationException::withMessages([
                    'ids' => 'This expense category cannot be deleted because it is used by an expense.',
                ]);
            }

            try {
                $category->delete();
            } catch (QueryException $exception) {
                if (! $this->isForeignKeyViolation($exception)) {
                    throw $exception;
                }

                throw ValidationException::withMessages([
                    'ids' => 'This expense category cannot be deleted because it is used by an expense.',
                ]);
            }
        }
    }

    private function isForeignKeyViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = $exception->errorInfo[1] ?? null;

        return $sqlState === '23000' || $driverCode === 1451 || $driverCode === 19;
    }
}
