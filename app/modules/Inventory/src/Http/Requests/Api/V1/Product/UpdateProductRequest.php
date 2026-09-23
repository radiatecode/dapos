<?php

namespace DA\Inventory\Http\Requests\Api\V1\Product;

class UpdateProductRequest extends ProductRequest
{
    protected function existingProductId(): ?int
    {
        $id = $this->route('id');

        return is_numeric($id) ? (int) $id : null;
    }
}
