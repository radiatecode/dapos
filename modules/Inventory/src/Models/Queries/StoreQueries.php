<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class StoreQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Store>
     */
    public function paginateNewestFirst(int $perPage = 15, ?string $name = null): LengthAwarePaginator
    {
        return $this->forAuthenticatedTenant()
            ->when(filled($name), fn (Builder $query) => $query->where('name', 'like', '%'.$name.'%'))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForTenant(int $id): Store
    {
        /** @var Store $store */
        $store = $this->forAuthenticatedTenant()->findOrFail($id);

        return $store;
    }

    public function select2(?string $search = null): Builder
    {
        return $this->forAuthenticatedTenant()
            ->when($search, function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name');
    }

    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', auth()->user()->tenant_id);
    }
}
