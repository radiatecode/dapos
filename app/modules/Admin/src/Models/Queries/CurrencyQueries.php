<?php

namespace DA\Admin\Models\Queries;

use DA\Admin\Models\Currency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class CurrencyQueries extends BaseQueries
{
    /**
     * @return Collection<int, Currency>
     */
    public function activeOrderedByCode(): Collection
    {
        return $this->eloquentBuilder()
            ->where('is_active', true)
            ->orderBy('code')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return SupportCollection<int, string>
     */
    public function activeCodes(): SupportCollection
    {
        return $this->eloquentBuilder()
            ->where('is_active', true)
            ->orderBy('code')
            ->orderBy('id')
            ->pluck('code');
    }
}
