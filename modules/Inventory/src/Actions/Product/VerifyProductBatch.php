<?php

namespace DA\Inventory\Actions\Product;

use DA\Inventory\DTO\Product\ProductBatchRowDTO;
use DA\Inventory\DTO\Product\ProductBatchVerification;
use DA\Inventory\DTO\Product\ProductDTO;
use DA\Inventory\DTO\Product\ProductVariantDTO;
use DA\Inventory\Enums\BarcodeType;
use DA\Inventory\Enums\ItemTaxType;
use DA\Inventory\Enums\ProductType;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Unit;
use DA\Inventory\Services\Decimal;
use DA\Inventory\Services\ProductBatchReader;
use DA\Inventory\Services\VariantCodeGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VerifyProductBatch
{
    public function __construct(
        private ProductBatchReader $reader,
        private VariantCodeGenerator $codes,
    ) {}

    public function handle(UploadedFile $file, int $storeId): ProductBatchVerification
    {
        $parsed = $this->reader->read($file);

        if ($parsed->formatError !== null) {
            throw ValidationException::withMessages([
                'file' => $parsed->formatError,
            ]);
        }

        $categories = Category::queries()->idsGroupedByLowerName();
        $brands = Brand::queries()->idsGroupedByLowerName();
        $units = Unit::queries()->idsGroupedByLowerName();
        $skuCounts = $this->codeCounts($parsed->rows, 'sku');
        $barcodeCounts = $this->codeCounts($parsed->rows, 'barcode');
        $takenSkus = $this->takenCodes($parsed->rows, 'sku');
        $takenBarcodes = $this->takenCodes($parsed->rows, 'barcode');

        $preview = [];
        $resolved = [];
        $valid = true;

        foreach ($parsed->rows as $row) {
            $errors = $this->rowErrors(
                $row,
                $categories,
                $brands,
                $units,
                $skuCounts,
                $barcodeCounts,
                $takenSkus,
                $takenBarcodes,
            );

            if ($errors !== []) {
                $valid = false;
            }

            $preview[] = [
                'row' => $row->excelRow,
                'status' => $errors === [] ? 'ok' : 'error',
                'errors' => $errors,
                'data' => $row->values(),
            ];

            $resolved[] = [
                'row' => $row,
                'category_id' => $this->catalogId($categories, $row->category),
                'brand_id' => $this->catalogId($brands, $row->brand),
                'unit_id' => $this->catalogId($units, $row->unit),
            ];
        }

        return new ProductBatchVerification(
            valid: $valid,
            storeId: $storeId,
            rows: $preview,
            products: $valid ? $this->products($resolved, $storeId) : [],
        );
    }

    /**
     * @param  list<ProductBatchRowDTO>  $rows
     * @return array<string, int>
     */
    private function codeCounts(array $rows, string $field): array
    {
        $counts = [];

        foreach ($rows as $row) {
            $value = $field === 'sku' ? $row->sku : $row->barcode;

            if ($value === '') {
                continue;
            }

            $key = Str::lower($value);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param  list<ProductBatchRowDTO>  $rows
     * @return list<string>
     */
    private function takenCodes(array $rows, string $field): array
    {
        $codes = [];

        foreach ($rows as $row) {
            $value = $field === 'sku' ? $row->sku : $row->barcode;

            if ($value !== '') {
                $codes[] = $value;
            }
        }

        $taken = $field === 'sku'
            ? ProductVariant::queries()->takenSkus($codes)
            : ProductVariant::queries()->takenBarcodes($codes);

        return $taken
            ->map(fn (mixed $code): string => Str::lower((string) $code))
            ->all();
    }

    /**
     * @param  array<string, list<int>>  $categories
     * @param  array<string, list<int>>  $brands
     * @param  array<string, list<int>>  $units
     * @param  array<string, int>  $skuCounts
     * @param  array<string, int>  $barcodeCounts
     * @param  list<string>  $takenSkus
     * @param  list<string>  $takenBarcodes
     * @return list<string>
     */
    private function rowErrors(
        ProductBatchRowDTO $row,
        array $categories,
        array $brands,
        array $units,
        array $skuCounts,
        array $barcodeCounts,
        array $takenSkus,
        array $takenBarcodes,
    ): array {
        $errors = [];

        if ($row->name === '') {
            $errors[] = 'Name is required.';
        } elseif (Str::length($row->name) > 255) {
            $errors[] = 'Name may not be greater than 255 characters.';
        }

        $errors = array_merge($errors, $this->catalogErrors('Category', $row->category, $categories));
        $errors = array_merge($errors, $this->catalogErrors('Brand', $row->brand, $brands));
        $errors = array_merge($errors, $this->catalogErrors('Unit', $row->unit, $units));
        $errors = array_merge($errors, $this->codeErrors('SKU', $row->sku, $skuCounts, $takenSkus));
        $errors = array_merge($errors, $this->codeErrors('barcode', $row->barcode, $barcodeCounts, $takenBarcodes));

        $costError = $this->decimalError('Cost price', $row->costPrice, true);

        if ($costError !== null) {
            $errors[] = $costError;
        }

        $sellingError = $this->decimalError('Selling price', $row->sellingPrice, true);

        if ($sellingError !== null) {
            $errors[] = $sellingError;
        }

        $quantityError = $this->decimalError('Quantity', $row->quantity, false);

        if ($quantityError !== null) {
            $errors[] = $quantityError;
        }

        $minStockError = $this->minStockError($row->minStockLevel);

        if ($minStockError !== null) {
            $errors[] = $minStockError;
        }

        return $errors;
    }

    /**
     * @param  array<string, list<int>>  $grouped
     * @return list<string>
     */
    private function catalogErrors(string $label, string $value, array $grouped): array
    {
        if ($value === '') {
            return ["{$label} is required."];
        }

        $matches = $grouped[Str::lower($value)] ?? [];

        if ($matches === []) {
            return ["{$label} \"{$value}\" was not found."];
        }

        if (count($matches) > 1) {
            return ["{$label} \"{$value}\" matches more than one record."];
        }

        return [];
    }

    /**
     * @param  array<string, list<int>>  $grouped
     */
    private function catalogId(array $grouped, string $value): ?int
    {
        $matches = $grouped[Str::lower($value)] ?? [];

        if (count($matches) !== 1) {
            return null;
        }

        return $matches[0];
    }

    /**
     * @param  array<string, int>  $counts
     * @param  list<string>  $taken
     * @return list<string>
     */
    private function codeErrors(string $label, string $value, array $counts, array $taken): array
    {
        if ($value === '') {
            return [];
        }

        $errors = [];

        if (Str::length($value) > 100) {
            $errors[] = "The {$label} may not be greater than 100 characters.";
        }

        $key = Str::lower($value);

        if (($counts[$key] ?? 0) > 1) {
            $errors[] = "The {$label} is used more than once in this file.";
        }

        if (in_array($key, $taken, true)) {
            $errors[] = "The {$label} has already been taken.";
        }

        return $errors;
    }

    private function decimalError(string $label, string $value, bool $required): ?string
    {
        if ($value === '') {
            return $required ? "{$label} is required." : null;
        }

        if (preg_match('/^\d+(\.\d+)?$/', $value) !== 1) {
            return "{$label} must be a number greater than or equal to 0.";
        }

        if (preg_match('/\.\d{5,}$/', $value) === 1) {
            return "{$label} may not have more than 4 decimal places.";
        }

        if (strlen(explode('.', $value, 2)[0]) > 11) {
            return "{$label} is too large.";
        }

        return null;
    }

    private function minStockError(string $value): ?string
    {
        if ($value === '') {
            return 'Min stock level is required.';
        }

        if (preg_match('/^(\d+)(?:\.0+)?$/', $value) !== 1) {
            return 'Min stock level must be a whole number greater than or equal to 0.';
        }

        if ((int) $value > 2147483647) {
            return 'Min stock level is too large.';
        }

        return null;
    }

    /**
     * @param  list<array{row: ProductBatchRowDTO, category_id: int|null, brand_id: int|null, unit_id: int|null}>  $resolved
     * @return list<ProductDTO>
     */
    private function products(array $resolved, int $storeId): array
    {
        $reservedSkus = [];
        $reservedBarcodes = [];

        foreach ($resolved as $item) {
            if ($item['row']->sku !== '') {
                $reservedSkus[] = $item['row']->sku;
            }

            if ($item['row']->barcode !== '') {
                $reservedBarcodes[] = $item['row']->barcode;
            }
        }

        $products = [];

        foreach ($resolved as $item) {
            $row = $item['row'];
            $sku = $row->sku;

            if ($sku === '') {
                $sku = $this->codes->nextSku($reservedSkus);
                $reservedSkus[] = $sku;
            }

            $barcode = $row->barcode;

            if ($barcode === '') {
                $barcode = $this->codes->nextBarcode($reservedBarcodes);
                $reservedBarcodes[] = $barcode;
            }

            $cost = Decimal::normalize($row->costPrice);
            $selling = Decimal::normalize($row->sellingPrice);
            $quantity = Decimal::normalize($row->quantity === '' ? '0' : $row->quantity);

            $products[] = new ProductDTO(
                categoryId: (int) $item['category_id'],
                brandId: (int) $item['brand_id'],
                unitId: (int) $item['unit_id'],
                name: $row->name,
                slug: null,
                description: $row->description !== '' ? $row->description : null,
                image: null,
                productType: ProductType::Simple,
                trackInventory: true,
                isActive: true,
                isStockOut: false,
                manufactureDate: null,
                expireDate: null,
                warrantyInDays: null,
                guaranteeInDays: null,
                attributes: [],
                variants: [
                    new ProductVariantDTO(
                        id: null,
                        sku: $sku,
                        barcode: $barcode,
                        barcodeType: BarcodeType::Code128,
                        name: null,
                        costPrice: $cost,
                        sellingPrice: $selling,
                        compareAtPrice: number_format(abs((float) $cost - (float) $selling), 4, '.', ''),
                        weight: null,
                        quantity: $quantity,
                        minStockLevel: (int) $row->minStockLevel,
                        image: null,
                        isDefault: true,
                        isActive: true,
                        attributeValues: [],
                    ),
                ],
                storeId: $storeId,
                taxIds: [],
                itemTaxType: ItemTaxType::EXCLUSIVE->value,
            );
        }

        return $products;
    }
}
