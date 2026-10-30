<?php

namespace DA\Expense\Models\Queries;

use App\Models\Scopes\AllocatedStoreScope;
use DA\Expense\DTO\Report\ExpenseReportFilterDTO;
use DA\Expense\Enums\ExpenseStatus;
use DA\Expense\Models\Expense;
use DA\Expense\Models\ExpenseCategory;
use DA\Inventory\Services\Decimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExpenseQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Expense>
     */
    public function paginateNewestFirst(
        int $perPage = 15,
        ?ExpenseStatus $status = null,
        ?int $categoryId = null,
        ?int $storeId = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        return $this->forAuthenticatedTenant()
            ->with(['category', 'store'])
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($categoryId !== null, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($storeId !== null, fn (Builder $query) => $query->where('store_id', $storeId))
            ->when(filled($search), fn (Builder $query) => $query->where('description', 'like', '%'.$search.'%'))
            ->when(filled($dateFrom), fn (Builder $query) => $query->whereDate('expense_date', '>=', $dateFrom))
            ->when(filled($dateTo), fn (Builder $query) => $query->whereDate('expense_date', '<=', $dateTo))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForTenant(int $id): Expense
    {
        /** @var Expense $expense */
        $expense = $this->forAuthenticatedTenant()
            ->with(['category', 'store'])
            ->findOrFail($id);

        return $expense;
    }

    public function existsForCategory(int $categoryId): bool
    {
        return $this->eloquentBuilder()
            ->withoutGlobalScope(AllocatedStoreScope::class)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('category_id', $categoryId)
            ->exists();
    }

    /**
     * @return array{
     *     total_expenses: int,
     *     total_amount: string,
     *     pending_count: int,
     *     approved_count: int,
     *     rejected_count: int,
     *     categories: list<array{
     *         id: int,
     *         category_name: string,
     *         is_active: bool,
     *         count: int,
     *         amount: string
     *     }>
     * }
     */
    public function summary(): array
    {
        $row = $this->forAuthenticatedTenant()
            ->toBase()
            ->selectRaw(
                'COUNT(*) as total_expenses,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(status = ?), 0) as pending_count,
                COALESCE(SUM(status = ?), 0) as approved_count,
                COALESCE(SUM(status = ?), 0) as rejected_count',
                [
                    ExpenseStatus::Pending->value,
                    ExpenseStatus::Approved->value,
                    ExpenseStatus::Rejected->value,
                ],
            )
            ->first();

        $amounts = $this->forAuthenticatedTenant()
            ->toBase()
            ->select('category_id')
            ->selectRaw('COUNT(*) as expense_count, COALESCE(SUM(amount), 0) as amount')
            ->groupBy('category_id')
            ->get()
            ->keyBy(fn (object $item): int => (int) $item->category_id);

        $categories = ExpenseCategory::query()
            ->orderBy('category_name')
            ->orderBy('id')
            ->get(['id', 'category_name', 'is_active']);

        return [
            'total_expenses' => (int) ($row?->total_expenses ?? 0),
            'total_amount' => Decimal::normalize($row?->total_amount ?? 0),
            'pending_count' => (int) ($row?->pending_count ?? 0),
            'approved_count' => (int) ($row?->approved_count ?? 0),
            'rejected_count' => (int) ($row?->rejected_count ?? 0),
            'categories' => $categories->map(function (ExpenseCategory $category) use ($amounts): array {
                $stats = $amounts->get($category->id);

                return [
                    'id' => $category->id,
                    'category_name' => $category->category_name,
                    'is_active' => $category->is_active,
                    'count' => (int) ($stats?->expense_count ?? 0),
                    'amount' => Decimal::normalize($stats?->amount ?? 0),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Expense>
     */
    public function paginateReport(ExpenseReportFilterDTO $filters, int $perPage): LengthAwarePaginator
    {
        return $this->reportRowsQuery($filters)
            ->paginate($perPage);
    }

    /**
     * Status is omitted so the report totals stay in place when a status pill filters the list.
     *
     * @return array{
     *     total_expenses: int,
     *     total_amount: string,
     *     pending_count: int,
     *     pending_amount: string,
     *     approved_count: int,
     *     approved_amount: string,
     *     rejected_count: int,
     *     rejected_amount: string
     * }
     */
    public function reportTotals(ExpenseReportFilterDTO $filters): array
    {
        $row = $this->reportFiltered($filters, false)
            ->toBase()
            ->selectRaw(
                'COUNT(*) as total_expenses,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(status = ?), 0) as pending_count,
                COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as pending_amount,
                COALESCE(SUM(status = ?), 0) as approved_count,
                COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as approved_amount,
                COALESCE(SUM(status = ?), 0) as rejected_count,
                COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as rejected_amount',
                [
                    ExpenseStatus::Pending->value,
                    ExpenseStatus::Pending->value,
                    ExpenseStatus::Approved->value,
                    ExpenseStatus::Approved->value,
                    ExpenseStatus::Rejected->value,
                    ExpenseStatus::Rejected->value,
                ],
            )
            ->first();

        return [
            'total_expenses' => (int) ($row?->total_expenses ?? 0),
            'total_amount' => Decimal::normalize($row?->total_amount ?? 0),
            'pending_count' => (int) ($row?->pending_count ?? 0),
            'pending_amount' => Decimal::normalize($row?->pending_amount ?? 0),
            'approved_count' => (int) ($row?->approved_count ?? 0),
            'approved_amount' => Decimal::normalize($row?->approved_amount ?? 0),
            'rejected_count' => (int) ($row?->rejected_count ?? 0),
            'rejected_amount' => Decimal::normalize($row?->rejected_amount ?? 0),
        ];
    }

    /**
     * @return Collection<int, Expense>
     */
    public function reportRows(ExpenseReportFilterDTO $filters): Collection
    {
        return $this->reportRowsQuery($filters)->get();
    }

    /**
     * @return Builder<Expense>
     */
    private function reportRowsQuery(ExpenseReportFilterDTO $filters): Builder
    {
        return $this->reportFiltered($filters, true)
            ->with(['category', 'store'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id');
    }

    /**
     * @return Builder<Expense>
     */
    private function reportFiltered(ExpenseReportFilterDTO $filters, bool $includeStatus): Builder
    {
        return $this->forAuthenticatedTenant()
            ->when($includeStatus && $filters->status !== null, fn (Builder $query) => $query->where('status', $filters->status))
            ->when($filters->categoryId !== null, fn (Builder $query) => $query->where('category_id', $filters->categoryId))
            ->when($filters->storeId !== null, fn (Builder $query) => $query->where('store_id', $filters->storeId))
            ->when(filled($filters->search), fn (Builder $query) => $query->where('description', 'like', '%'.$filters->search.'%'))
            ->when(filled($filters->dateFrom), fn (Builder $query) => $query->whereDate('expense_date', '>=', $filters->dateFrom))
            ->when(filled($filters->dateTo), fn (Builder $query) => $query->whereDate('expense_date', '<=', $filters->dateTo));
    }

    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', auth()->user()->tenant_id);
    }
}
