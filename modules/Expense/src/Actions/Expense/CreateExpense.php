<?php

namespace DA\Expense\Actions\Expense;

use App\Enums\Permission;
use DA\Expense\DTO\Expense\ExpenseDTO;
use DA\Expense\Enums\ExpenseStatus;
use DA\Expense\Models\Expense;

class CreateExpense
{
    public function handle(ExpenseDTO $dto): Expense
    {
        $user = auth()->user();
        $userId = $user->id;
        $status = $this->statusFor($dto);

        $expense = new Expense;
        $expense->tenant_id = $user->tenant_id;
        $expense->category_id = $dto->categoryId;
        $expense->store_id = $dto->storeId;
        $expense->description = $dto->description;
        $expense->amount = bcadd($dto->amount, '0', 4);
        $expense->expense_date = $dto->expenseDate;
        $expense->status = $status;
        $expense->approval_time = $status === ExpenseStatus::Pending ? null : now();
        $expense->created_by = $userId;
        $expense->updated_by = $userId;

        if ($dto->attachment !== null) {
            $expense->attachment = $dto->attachment->store('expenses', 'public');
        }

        $expense->save();

        return $expense->refresh()->load(['category', 'store']);
    }

    /**
     * A chosen approved or rejected status is kept only when the user can approve expenses.
     * The client cannot set the approval time.
     */
    private function statusFor(ExpenseDTO $dto): ExpenseStatus
    {
        $status = $dto->status ?? ExpenseStatus::Pending;

        if ($status === ExpenseStatus::Pending) {
            return ExpenseStatus::Pending;
        }

        $user = auth()->user();

        if ($user->is_super_admin || $user->hasPermission(Permission::ExpensesApprove)) {
            return $status;
        }

        return ExpenseStatus::Pending;
    }
}
