<?php

use App\Enums\Permission;
use DA\Expense\Http\Controllers\Api\V1\ExpenseCategoryController;
use DA\Expense\Http\Controllers\Api\V1\ExpenseController;
use DA\Expense\Http\Controllers\Api\V1\ExpenseReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum'])->group(function (): void {
    Route::middleware('feature:expenses')->group(function (): void {
        Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])
            ->middleware('permission:'.Permission::ExpenseCategoriesView->value)
            ->name('expense-categories.index');
        Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])
            ->middleware('permission:'.Permission::ExpenseCategoriesCreate->value)
            ->name('expense-categories.store');
        Route::post('expense-categories/delete', [ExpenseCategoryController::class, 'destroy'])
            ->middleware('permission:'.Permission::ExpenseCategoriesDelete->value)
            ->name('expense-categories.destroy');
        Route::get('expense-categories/{id}', [ExpenseCategoryController::class, 'show'])
            ->middleware('permission:'.Permission::ExpenseCategoriesView->value)
            ->name('expense-categories.show');
        Route::put('expense-categories/{id}', [ExpenseCategoryController::class, 'update'])
            ->middleware('permission:'.Permission::ExpenseCategoriesUpdate->value)
            ->name('expense-categories.update');

        Route::get('expenses', [ExpenseController::class, 'index'])
            ->middleware('permission:'.Permission::ExpensesView->value)
            ->name('expenses.index');
        Route::get('expenses/summary', [ExpenseController::class, 'summary'])
            ->middleware('permission:'.Permission::ExpensesView->value)
            ->name('expenses.summary');
        Route::post('expenses', [ExpenseController::class, 'store'])
            ->middleware('permission:'.Permission::ExpensesCreate->value)
            ->name('expenses.store');
        Route::post('expenses/delete', [ExpenseController::class, 'destroy'])
            ->middleware('permission:'.Permission::ExpensesDelete->value)
            ->name('expenses.destroy');
        Route::get('expenses/{id}', [ExpenseController::class, 'show'])
            ->middleware('permission:'.Permission::ExpensesView->value)
            ->name('expenses.show');
        Route::post('expenses/{id}', [ExpenseController::class, 'update'])
            ->middleware('permission:'.Permission::ExpensesUpdate->value)
            ->name('expenses.update');
        Route::put('expenses/{id}/status', [ExpenseController::class, 'updateStatus'])
            ->middleware('permission:'.Permission::ExpensesApprove->value)
            ->name('expenses.status');
    });

    Route::prefix('reports/expenses')
        ->name('reports.expenses.')
        ->middleware(['permission:'.Permission::ExpensesReportsView->value, 'feature:expense_report'])
        ->group(function (): void {
            Route::get('/', [ExpenseReportController::class, 'index'])->name('index');
            Route::get('summary', [ExpenseReportController::class, 'summary'])->name('summary');
            Route::get('export/pdf', [ExpenseReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('export/excel', [ExpenseReportController::class, 'exportExcel'])->name('export.excel');
        });
});
