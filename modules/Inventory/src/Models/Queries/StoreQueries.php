<?php

namespace DA\Inventory\Models\Queries;

use App\Models\Scopes\AllocatedStoreScope;
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

    /**
     * Store ids that belong to the tenant. Allocation is ignored so assignment
     * can tell a missing store from a store the actor is not allowed to give.
     *
     * @param  list<int>  $ids
     * @return list<int>
     */
    public function idsExistingInTenant(array $ids, int $tenantId): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->eloquentBuilder()
            ->withoutGlobalScope(AllocatedStoreScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
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
