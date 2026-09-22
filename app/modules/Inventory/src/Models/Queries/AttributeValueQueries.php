<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\AttributeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AttributeValueQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, AttributeValue>
     */
    public function paginateForAttribute(int $attributeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->eloquentBuilder()
            ->where('attribute_id', $attributeId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function slugExistsForAttribute(int $attributeId, string $slug, ?int $ignoreId = null): bool
    {
        return $this->eloquentBuilder()
            ->where('attribute_id', $attributeId)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function uniqueSlugFromForAttribute(int $attributeId, string $name, string $fallback, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: $fallback;
        $slug = $base;
        $suffix = 1;

        while ($this->slugExistsForAttribute($attributeId, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
