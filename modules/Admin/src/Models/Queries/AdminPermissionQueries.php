<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Models\AdminPermission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class AdminPermissionQueries extends BaseQueries
{
    /**
     * @return BaseCollection<string, Collection<int, AdminPermission>>
     */
    public function groupedByGroup(): BaseCollection
    {
        return $this->eloquentBuilder()
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group');
    }
}
