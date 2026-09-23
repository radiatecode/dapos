<?php

namespace DA\Inventory\Actions\Product;

use DA\Inventory\DTO\Product\ProductDTO;
use DA\Inventory\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateProduct
{
    public function __construct(private ProductWriter $writer) {}

    public function handle(int $id, ProductDTO $dto): Product
    {
        $product = Product::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        return DB::transaction(function () use ($product, $dto): Product {
            $this->writer->fill($product, $dto);

            $product->save();

            $this->writer->sync($product, $dto);

            return Product::queries()->findForDetail($product->id);
        });
    }
}
