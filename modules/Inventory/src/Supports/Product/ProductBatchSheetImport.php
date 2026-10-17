<?php

namespace DA\Inventory\Supports\Product;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class ProductBatchSheetImport implements ToCollection, WithCalculatedFormulas
{
    public function collection(Collection $collection): void {}
}
