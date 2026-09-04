<?php

namespace DA\Admin\Models\Queries;

use App\Models\Concerns\ResolveQueryBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

    protected function modelPath(): string
    {
        return 'DA\\Admin\\Models\\';
    }
}
