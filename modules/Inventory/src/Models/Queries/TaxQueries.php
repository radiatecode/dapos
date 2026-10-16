<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Models\Tax;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaxQueries extends BaseQueries
{
    public function findById($id)
    {
        return $this->eloquentBuilder()
            ->findOr(
                $id,
                fn () => throw new ModelNotFoundException('Tax not found for the given id.'.$id)
            );
    }

    public function getTaxes(int $paginate = 20)
    {
        return $this->queryBuilder()
            ->leftJoin('users as creator', 'creator.id', '=', 'taxes.created_by')
            ->leftJoin('users as updater', 'updater.id', '=', 'taxes.updated_by')
            ->where('taxes.tenant_id', auth()->user()->tenant_id)
            ->orderByDesc('taxes.id')
            ->select(
                'taxes.*',
                'creator.name as creator_name',
                'updater.name as updater_name',
            )
            ->paginate($paginate);
    }

    /**
     * @return Builder<Tax>
     */
    public function select2(?string $search = null): Builder
    {
        return $this->eloquentBuilder()
            ->where('is_active', true)
            ->when($search, function ($query) use ($search) {
                $query->where('tax_name', 'like', "%{$search}%");
            })
            ->orderBy('tax_name');
    }

    public function getDataByIds(array $ids)
    {
        return $this->eloquentBuilder()
            ->whereIn('id', $ids)
            ->select('id', 'tax_name', 'created_by')
            ->get();
    }
}
