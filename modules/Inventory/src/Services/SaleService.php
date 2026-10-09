<?php

namespace DA\Inventory\Services;

use DA\Inventory\DTO\Sale\SaleDTO;
use DA\Inventory\Enums\ItemTaxType;
use DA\Inventory\Enums\TaxType;
use DA\Inventory\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class SaleService
{
    /**
     * Line subtotal is quantity × selling price.
     * Line total subtracts the line discount and adds line tax when that tax is exclusive.
     * Grand total is the sum of line totals, minus the header discount, plus header tax, shipping, and rounding.
     *
     * @return array{subtotal: string, total_items: string, grand_total: string, lines: list<array{subtotal: string, total: string}>}
     */
    public function calculate(SaleDTO $dto): array
    {
        $totalItems = 0;
        $lines = [];
        $gross = 0;

        foreach ($dto->items as $index => $item) {
            $subtotal = (int) $item->quantity * (float) $item->unitPrice;

            $taxAmount = 0;

            if ($item->itemTaxType === ItemTaxType::EXCLUSIVE->value) {
                $taxAmount = array_reduce($item->taxes, function ($carry, $tax) use ($subtotal) {
                    $taxLineAmount = $tax['tax_type'] === TaxType::PERCENTAGE->value
                    ? ($subtotal * ($tax['tax_rate'] ?? 0) / 100)
                    : $tax['tax_rate'] ?? 0;

                    return $carry + $taxLineAmount;
                }, 0);
            }

            $nettotal = $subtotal + $taxAmount;

            $gross = $gross + $nettotal;

            $totalItems = $totalItems + $item->quantity;

            $lines[] = [
                'unitprice' => $item->unitPrice,
                'quantity' => $item->quantity,
                'subtotal' => $subtotal,
                'total' => $nettotal,
                'taxamount' => $taxAmount,
            ];
        }

        $grandTotal = $gross - $dto->discountAmount;

        if ($grandTotal < 0) {
            throw ValidationException::withMessages([
                'grand_total' => 'The grand total cannot be negative.',
            ]);
        }

        return [
            'subtotal' => $gross,
            'total_items' => $totalItems,
            'grand_total' => $grandTotal,
            'discount_amount' => $dto->discountAmount,
            'cart_items' => $lines,
        ];
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     */
    public function assertSellable(SaleDTO $dto, Collection $variants): void
    {
        foreach ($dto->items as $index => $item) {
            $variant = $variants->get($item->productVariantId);

            if (! $variant instanceof ProductVariant || $variant->product === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_variant_id" => 'The selected product variant is invalid.',
                ]);
            }

            if (! $variant->is_active || ! $variant->product->is_active) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_variant_id" => 'The selected product is not available for sale.',
                ]);
            }

            if ($variant->selling_price === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => 'The product does not have a selling price.',
                ]);
            }

            if (Decimal::compare($item->unitPrice, $variant->selling_price) !== 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => 'The unit price does not match the product selling price.',
                ]);
            }
        }
    }

    public function variantLabel(ProductVariant $variant): string
    {
        $name = trim((string) $variant->name);

        if ($name === '') {
            $name = trim((string) $variant->product?->name);
        }

        if ($name === '') {
            $name = (string) $variant->sku;
        }

        return mb_substr($name, 0, 255);
    }
}
