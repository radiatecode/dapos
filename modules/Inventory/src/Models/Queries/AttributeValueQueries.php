<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\AttributeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AttributeValueQueries extends BaseQueries
{
    /**
     * @return LengthAwarePaginator<int, AttributeValue>
     */
    public function paginateForAttribute(int $perPage = 15, ?int $attributeId = null, ?string $value = null): LengthAwarePaginator
    {
        return $this->eloquentBuilder()
            ->with('attribute')
            ->when($attributeId, fn ($query) => $query->where('attribute_id', $attributeId))
            ->when(filled($value), fn ($query) => $query->where('value', 'like', '%'.$value.'%'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function select2(int $attributeId, ?string $search = null): Builder
    {
        return $this->eloquentBuilder()
            ->where('attribute_id', $attributeId)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('value', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('value');
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
