<?php

namespace DA\Expense\Models\Queries;

use DA\Expense\Models\ExpenseCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ExpenseCategoryQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, ExpenseCategory>
     */
    public function paginateNewestFirst(int $perPage = 15, ?string $categoryName = null): LengthAwarePaginator
    {
        return $this->forAuthenticatedTenant()
            ->when(filled($categoryName), fn (Builder $query) => $query->where('category_name', 'like', '%'.$categoryName.'%'))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function nameForAuthenticatedTenant(int $id): ?string
    {
        $name = $this->forAuthenticatedTenant()
            ->whereKey($id)
            ->value('category_name');

        return $name === null ? null : (string) $name;
    }

    public function findForTenant(int $id): ExpenseCategory
    {
        /** @var ExpenseCategory $category */
        $category = $this->forAuthenticatedTenant()->findOrFail($id);

        return $category;
    }

    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', auth()->user()->tenant_id);
    }
}
