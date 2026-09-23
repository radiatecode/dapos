<?php

namespace DA\Inventory\Actions\Product;

use DA\Inventory\DTO\Product\ProductDTO;
use DA\Inventory\DTO\Product\ProductVariantDTO;
use DA\Inventory\Enums\ProductType;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\ProductAttribute;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\VariantAttributeValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductWriter
{
    public function fill(Product $product, ProductDTO $dto): void
    {
        $product->tenant_id = auth()->user()->tenant_id;
        $product->category_id = $dto->categoryId;
        $product->brand_id = $dto->brandId;
        $product->unit_id = $dto->unitId;
        $product->name = $dto->name;
        $product->slug = $dto->slug !== null && $dto->slug !== ''
            ? $dto->slug
            : Product::queries()->uniqueSlugFrom($dto->name, 'product', $product->exists ? $product->id : null);
        $product->description = $dto->description;
        $product->product_type = $dto->productType;
        $product->track_inventory = $dto->trackInventory;
        $product->is_active = $dto->isActive;
        $product->is_stock_out = $dto->isStockOut;
        $product->manufacture_date = $dto->manufactureDate;
        $product->expire_date = $dto->expireDate;
        $product->warranty_in_days = $dto->warrantyInDays;
        $product->guarantee_in_days = $dto->guaranteeInDays;

        if ($dto->image !== null) {
            if (is_string($product->image) && $product->image !== '') {
                Storage::disk('public')->delete($product->image);
            }

            $product->image = $dto->image->store('catalog/products', 'public');
        }
    }

    public function sync(Product $product, ProductDTO $dto): void
    {
        $this->syncAttributes($product, $dto);
        $this->syncVariants($product, $dto);
    }

    private function syncAttributes(Product $product, ProductDTO $dto): void
    {
        if ($product->product_type === ProductType::Simple) {
            ProductAttribute::query()->where('product_id', $product->id)->delete();

            return;
        }

        $keepAttributeIds = [];

        foreach ($dto->attributes as $attribute) {
            $row = ProductAttribute::query()->firstOrNew([
                'product_id' => $product->id,
                'attribute_id' => $attribute->attributeId,
            ]);
            $row->sort_order = $attribute->sortOrder;
            $row->is_required = $attribute->isRequired;
            $row->save();
            $keepAttributeIds[] = $attribute->attributeId;
        }

        ProductAttribute::query()
            ->where('product_id', $product->id)
            ->whereNotIn('attribute_id', $keepAttributeIds)
            ->delete();
    }

    private function syncVariants(Product $product, ProductDTO $dto): void
    {
        /** @var Collection<int, ProductVariant> $existing */
        $existing = $product->variants()->get()->keyBy('id');
        $this->releaseVariantCodes($existing);

        $hasExplicitDefault = collect($dto->variants)->contains(fn (ProductVariantDTO $variant): bool => $variant->isDefault);
        $retainedIds = [];

        foreach ($dto->variants as $index => $variantDto) {
            $variant = $this->resolveVariant($existing, $variantDto, $product);
            $this->fillVariant($variant, $variantDto, $product, $hasExplicitDefault, $index);
            $variant->save();
            $retainedIds[] = $variant->id;
            $this->syncAttributeValues($variant, $product, $variantDto);
        }

        $existing->except($retainedIds)->each(function (ProductVariant $variant): void {
            $variant->is_default = false;
            $variant->save();
            $variant->attributeValues()->delete();
            $variant->delete();
        });
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     */
    private function releaseVariantCodes(Collection $variants): void
    {
        foreach ($variants as $variant) {
            $variant->sku = 'tmp-'.Str::lower((string) Str::ulid());
            $variant->barcode = 'tmp-'.Str::lower((string) Str::ulid());
            $variant->save();
        }
    }

    /**
     * @param  Collection<int, ProductVariant>  $existing
     */
    private function resolveVariant(Collection $existing, ProductVariantDTO $dto, Product $product): ProductVariant
    {
        if ($dto->id !== null && $existing->has($dto->id)) {
            return $existing->get($dto->id);
        }

        $variant = new ProductVariant;
        $variant->product_id = $product->id;
        $variant->tenant_id = auth()->user()->tenant_id;

        return $variant;
    }

    private function fillVariant(
        ProductVariant $variant,
        ProductVariantDTO $dto,
        Product $product,
        bool $hasExplicitDefault,
        int $index,
    ): void {
        $variant->tenant_id = auth()->user()->tenant_id;
        $variant->sku = $dto->sku;
        $variant->barcode = $dto->barcode;
        $variant->barcode_type = $dto->barcodeType;
        $variant->name = $dto->name;
        $variant->cost_price = $dto->costPrice;
        $variant->selling_price = $dto->sellingPrice;
        $variant->compare_at_price = $dto->compareAtPrice;
        $variant->weight = $dto->weight;
        $variant->is_active = $dto->isActive;
        $variant->is_default = $product->product_type === ProductType::Simple
            || ($hasExplicitDefault ? $dto->isDefault : $index === 0);

        if ($product->track_inventory) {
            $variant->quantity = $dto->quantity;
            $variant->min_stock_level = $dto->minStockLevel;
        } else {
            $variant->quantity = null;
            $variant->min_stock_level = null;
        }

        if ($dto->image !== null) {
            if (is_string($variant->image) && $variant->image !== '') {
                Storage::disk('public')->delete($variant->image);
            }

            $variant->image = $dto->image->store('catalog/products/variants', 'public');
        }
    }

    private function syncAttributeValues(ProductVariant $variant, Product $product, ProductVariantDTO $dto): void
    {
        $variant->attributeValues()->delete();

        if ($product->product_type !== ProductType::Variable) {
            return;
        }

        foreach ($dto->attributeValues as $attributeValue) {
            $row = new VariantAttributeValue;
            $row->variant_id = $variant->id;
            $row->attribute_id = $attributeValue->attributeId;
            $row->attribute_value_id = $attributeValue->attributeValueId;
            $row->save();
        }
    }
}
