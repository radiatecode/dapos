<?php

namespace DA\Inventory\Actions\Product;

use DA\Inventory\DTO\Product\ProductDTO;
use DA\Inventory\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateProduct
{
    public function __construct(
        private ProductWriter $writer,
        private GuardProductStock $guard,
        private PostOpeningStock $openingStock,
    ) {}

    public function handle(int $id, ProductDTO $dto): Product
    {
        $product = Product::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        if (! $product->authorizeAction($product, 'update')) {
            throw ValidationException::withMessages([
                'product' => 'You are not authorized to update this product.',
            ]);
        }

        return DB::transaction(function () use ($product, $dto): Product {
            $this->guard->handle($product, $dto);

            $this->writer->fill($product, $dto);

            $product->save();

            $this->writer->sync($product, $dto);

            $this->openingStock->handle($product, $dto->storeId);

            return Product::queries()->findForDetail($product->id);
        });
    }
}
