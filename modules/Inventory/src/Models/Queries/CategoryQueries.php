<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CategoryQueries extends BaseQueries
{
    /**
     * @return Collection<int, Category>
     */
    public function tree(): Collection
    {
        $categories = $this->eloquentBuilder()
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->get();

        $grouped = $categories->groupBy(fn (Category $category): int => $category->parent_id ?? 0);

        return $this->nest($grouped, 0);
    }

    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginateBySortOrder(int $perPage = 15): LengthAwarePaginator
    {
        return $this->eloquentBuilder()
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function hasChildren(int $categoryId): bool
    {
        return $this->eloquentBuilder()
            ->where('parent_id', $categoryId)
            ->exists();
    }

    /**
     * @return list<int>
     */
    public function descendantIds(int $categoryId): array
    {
        $ids = [];
        $frontier = [$categoryId];

        while ($frontier !== []) {
            $children = $this->eloquentBuilder()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $frontier = array_values(array_diff($children, $ids));
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    /**
     * @param  Collection<int|string, Collection<int, Category>>  $grouped
     * @return Collection<int, Category>
     */
    private function nest(Collection $grouped, int $parentId): Collection
    {
        return ($grouped->get($parentId) ?? collect())
            ->map(function (Category $category) use ($grouped): Category {
                $category->setRelation('children', $this->nest($grouped, $category->id));

                return $category;
            })
            ->values();
    }
}
