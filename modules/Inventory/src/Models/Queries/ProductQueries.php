<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Product;
use DA\Inventory\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        ?int $storeId = null,
    ): LengthAwarePaginator {
        return $this->filtered($name, $search, $categoryId, $brandId, $isActive)
            ->with($this->listingRelations($storeId))
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

    public function findForDetail(int $id, ?int $storeId = null): Product
    {
        /** @var Product $product */
        $product = $this->forAuthenticatedTenant()
            ->with($this->detailRelations($storeId))
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
            ->when($nameTerm !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$this->containsPattern($nameTerm).'%'))
            ->when($categoryId !== null, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($brandId !== null, fn (Builder $query) => $query->where('brand_id', $brandId))
            ->when($isActive !== null, fn (Builder $query) => $query->where('is_active', $isActive))
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $query->where(function (Builder $inner) use ($searchTerm): void {
                    $inner->where('name', 'like', '%'.$this->containsPattern($searchTerm).'%')
                        ->orWhereIn('id', ProductVariant::queries()->productIdsWithCodeStartingWith($searchTerm));
                });
            });
    }

    private function containsPattern(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
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
     * @return array<int|string, mixed>
     */
    public function detailRelations(?int $storeId = null): array
    {
        return $this->withStoreStock([
            'category',
            'brand',
            'unit',
            'taxes',
            'variants',
            'defaultVariant',
            'productAttributes.attribute',
            'variants.attributeValues.attribute',
            'variants.attributeValues.attributeValue',
        ], $storeId);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function listingRelations(?int $storeId): array
    {
        return $this->withStoreStock([
            'category',
            'brand',
            'unit',
            'taxes',
            'variants',
        ], $storeId);
    }

    /**
     * When a store is selected, variant quantity in the response is that store's available stock.
     *
     * @param  array<int|string, mixed>  $relations
     * @return array<int|string, mixed>
     */
    private function withStoreStock(array $relations, ?int $storeId): array
    {
        if ($storeId === null) {
            return $relations;
        }

        $constraint = function (Relation $query) use ($storeId): void {
            $query->where('store_id', $storeId);
        };
        $relations['variants.inventoryBalances'] = $constraint;

        if (in_array('defaultVariant', $relations, true)) {
            $relations['defaultVariant.inventoryBalances'] = $constraint;
        }

        return $relations;
    }
}
