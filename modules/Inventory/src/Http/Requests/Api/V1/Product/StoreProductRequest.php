<?php

namespace DA\Inventory\Http\Requests\Api\V1\Product;

class StoreProductRequest extends ProductRequest
{
    protected function existingProductId(): ?int
    {
        return null;
    }
}
