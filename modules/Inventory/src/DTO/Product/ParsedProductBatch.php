<?php

namespace DA\Inventory\DTO\Product;

final class ParsedProductBatch
{
    /**
     * @param  list<ProductBatchRowDTO>  $rows
     */
    public function __construct(
        public ?string $formatError,
        public array $rows = [],
    ) {}
}
