<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Models\AdminRole;

class AdminRoleQueries extends BaseQueries
{
    public function findBySlug(string $slug): ?AdminRole
    {
        return $this->eloquentBuilder()
            ->where('slug', $slug)
            ->first();
    }
}
