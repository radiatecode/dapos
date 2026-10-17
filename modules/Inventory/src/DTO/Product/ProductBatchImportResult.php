<?php

namespace DA\Inventory\DTO\Product;

use DA\Inventory\Models\Product;

final class ProductBatchImportResult
{
    /**
     * @param  list<Product>  $products
     */
    public function __construct(
        public ProductBatchVerification $verification,
        public array $products = [],
    ) {}
}
