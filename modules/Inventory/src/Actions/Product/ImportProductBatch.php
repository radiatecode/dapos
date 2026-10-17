<?php

namespace DA\Inventory\Actions\Product;

use DA\Inventory\DTO\Product\ProductBatchImportResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ImportProductBatch
{
    public function __construct(
        private VerifyProductBatch $verify,
        private CreateProduct $createProduct,
    ) {}

    public function handle(UploadedFile $file, int $storeId): ProductBatchImportResult
    {
        $verification = $this->verify->handle($file, $storeId);

        if (! $verification->valid) {
            return new ProductBatchImportResult($verification);
        }

        $products = DB::transaction(function () use ($verification): array {
            $created = [];

            foreach ($verification->products as $product) {
                $created[] = $this->createProduct->handle($product);
            }

            return $created;
        });

        return new ProductBatchImportResult($verification, $products);
    }
}
