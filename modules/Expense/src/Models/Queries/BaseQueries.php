<?php

namespace DA\Expense\Models\Queries;

use App\Models\Concerns\ResolveQueryBuilder;

class BaseQueries
{
    use ResolveQueryBuilder;

    protected function modelPath(): string
    {
        return 'DA\\Expense\\Models\\';
    }
}
