<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CustomerQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Customer>
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

    public function findForTenant(int $id): Customer
    {
        /** @var Customer $customer */
        $customer = $this->forAuthenticatedTenant()->findOrFail($id);

        return $customer;
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
