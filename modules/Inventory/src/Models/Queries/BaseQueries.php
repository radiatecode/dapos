<?php

namespace DA\Inventory\Models\Queries;

use App\Models\Concerns\ResolveQueryBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class BaseQueries
{
    use ResolveQueryBuilder;

    public function findById(int $id)
    {
        return $this->eloquentBuilder()
            ->findOr(
                $id,
                fn () => throw new ModelNotFoundException('Data not found for the given id.'.$id)
            );
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return $this->eloquentBuilder()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function uniqueSlugFrom(string $name, string $fallback, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: $fallback;
        $slug = $base;
        $suffix = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function modelPath(): string
    {
        return 'DA\\Inventory\\Models\\';
    }
}
