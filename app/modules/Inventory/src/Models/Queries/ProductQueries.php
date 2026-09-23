<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateNewestFirst(int $perPage = 15): LengthAwarePaginator
    {
        return $this->forAuthenticatedTenant()
            ->with(['category', 'brand', 'unit', 'defaultVariant'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForDetail(int $id): Product
    {
        /** @var Product $product */
        $product = $this->forAuthenticatedTenant()
            ->with($this->detailRelations())
            ->findOrFail($id);

        return $product;
    }

    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', auth()->user()->tenant_id);
    }

    /**
     * @return list<string>
     */
    public function detailRelations(): array
    {
        return [
            'category',
            'brand',
            'unit',
            'defaultVariant',
            'productAttributes.attribute',
            'variants.attributeValues.attribute',
            'variants.attributeValues.attributeValue',
        ];
    }
}
