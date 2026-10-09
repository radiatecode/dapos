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
    public function paginateNewestFirst(
        int $perPage = 15,
        ?string $name = null,
        ?string $search = null,
        ?int $categoryId = null,
        ?int $brandId = null,
        ?bool $isActive = null,
    ): LengthAwarePaginator {
        return $this->filtered($name, $search, $categoryId, $brandId, $isActive)
            ->with(['category', 'brand', 'unit', 'variants', 'defaultVariant'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return array{total_products: int, active_count: int, inactive_count: int}
     */
    public function summary(): array
    {
        $row = $this->forAuthenticatedTenant()
            ->selectRaw(
                'COUNT(*) as total_products,
                COALESCE(SUM(is_active = 1), 0) as active_count,
                COALESCE(SUM(is_active = 0), 0) as inactive_count',
            )
            ->first();

        return [
            'total_products' => (int) ($row?->total_products ?? 0),
            'active_count' => (int) ($row?->active_count ?? 0),
            'inactive_count' => (int) ($row?->inactive_count ?? 0),
        ];
    }

    public function findForDetail(int $id): Product
    {
        /** @var Product $product */
        $product = $this->forAuthenticatedTenant()
            ->with($this->detailRelations())
            ->findOrFail($id);

        return $product;
    }

    /**
     * @return Builder<Product>
     */
    private function filtered(
        ?string $name,
        ?string $search,
        ?int $categoryId,
        ?int $brandId,
        ?bool $isActive,
    ): Builder {
        $nameTerm = trim((string) $name);
        $searchTerm = trim((string) $search);

        return $this->forAuthenticatedTenant()
            ->when($nameTerm !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$nameTerm.'%'))
            ->when($categoryId !== null, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($brandId !== null, fn (Builder $query) => $query->where('brand_id', $brandId))
            ->when($isActive !== null, fn (Builder $query) => $query->where('is_active', $isActive))
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $like = '%'.addcslashes($searchTerm, '%_\\').'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('name', 'like', $like)
                        ->orWhereHas('variants', function (Builder $variants) use ($like): void {
                            $variants->where('sku', 'like', $like)
                                ->orWhere('barcode', 'like', $like);
                        });
                });
            });
    }

    /**
     * @return Builder<Product>
     */
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
            'taxes',
            'variants',
            'defaultVariant',
            'productAttributes.attribute',
            'variants.attributeValues.attribute',
            'variants.attributeValues.attributeValue',
        ];
    }
}
