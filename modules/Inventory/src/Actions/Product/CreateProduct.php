<?php

namespace DA\Inventory\Actions\Product;

use DA\Inventory\DTO\Product\ProductDTO;
use DA\Inventory\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    public function __construct(
        private ProductWriter $writer,
        private PostOpeningStock $openingStock,
    ) {}

    public function handle(ProductDTO $dto): Product
    {
        return DB::transaction(function () use ($dto): Product {
            $product = new Product;

            $this->writer->fill($product, $dto);

            $product->save();

            $this->writer->sync($product, $dto);

            $this->openingStock->handle($product, $dto->storeId);

            return Product::queries()->findForDetail($product->id);
        });
    }
}
