<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Models\AdminUser;

class AdminUserQueries extends BaseQueries
{
    public function findByEmail(string $email): ?AdminUser
    {
        return $this->eloquentBuilder()
            ->where('email', $email)
            ->first();
    }
}
