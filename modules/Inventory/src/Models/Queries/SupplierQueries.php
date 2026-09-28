<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SupplierQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Supplier>
     */
    public function paginateNewestFirst(
        int $perPage = 15,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
    ): LengthAwarePaginator {
        return $this->forAuthenticatedTenant()
            ->when(filled($name), fn (Builder $query) => $query->where('name', 'like', '%'.$name.'%'))
            ->when(filled($email), fn (Builder $query) => $query->where('email', 'like', '%'.$email.'%'))
            ->when(filled($phone), fn (Builder $query) => $query->where('phone', 'like', '%'.$phone.'%'))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForTenant(int $id): Supplier
    {
        /** @var Supplier $supplier */
        $supplier = $this->forAuthenticatedTenant()->findOrFail($id);

        return $supplier;
    }

    public function select2(?string $search = null): Builder
    {
        return $this->forAuthenticatedTenant()
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');
    }

    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', auth()->user()->tenant_id);
    }
}
