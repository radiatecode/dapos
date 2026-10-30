<?php

namespace DA\Expense\Actions\Expense;

use DA\Expense\Enums\ExpenseStatus;
use DA\Expense\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateExpenseStatus
{
    public function handle(int $id, ExpenseStatus $status): Expense
    {
        return DB::transaction(function () use ($id, $status): Expense {
            $expense = Expense::queries()->findForTenant($id);
            $expense = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if ($status === ExpenseStatus::Pending || $expense->status !== ExpenseStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Only a pending expense can be approved or rejected.',
                ]);
            }

            $expense->status = $status;
            $expense->approval_time = now();
            $expense->updated_by = auth()->id();
            $expense->save();

            return $expense->refresh()->load(['category', 'store']);
        });
    }
}
