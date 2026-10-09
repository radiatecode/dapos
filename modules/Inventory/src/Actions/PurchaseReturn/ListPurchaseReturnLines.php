<?php

namespace DA\Inventory\Actions\PurchaseReturn;

use DA\Inventory\Enums\InventoryLayerSourceType;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Models\InventoryLayer;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchaseOrderItem;
use DA\Inventory\Services\Decimal;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ListPurchaseReturnLines
{
    /**
     * Lines a received purchase can still return, with remaining quantity on that receipt layer.
     *
     * @return array{
     *     purchase: array{
     *         id: int,
     *         po_number: string|null,
     *         store_id: int,
     *         supplier_id: int,
     *         currency: string|null,
     *         supplier: array{id: int, name: string}|null,
     *         store: array{id: int, name: string}|null
     *     },
     *     items: list<array{
     *         purchase_order_item_id: int,
     *         product_variant_id: int,
     *         name: string,
     *         sku: string|null,
     *         image_url: string|null,
     *         unit_cost: string,
     *         purchased_quantity: string,
     *         available_quantity: string,
     *         tax_amount: string,
     *         tax_rate: string|null
     *     }>
     * }
     */
    public function handle(int $purchaseId): array
    {
        $purchase = Purchase::queries()->findForDetail($purchaseId);

        if ($purchase->status !== PurchaseStatus::Received) {
            throw ValidationException::withMessages([
                'purchase_id' => 'Only a received purchase can be returned.',
            ]);
        }

        $layers = InventoryLayer::query()
            ->where('tenant_id', $purchase->tenant_id)
            ->where('store_id', $purchase->store_id)
            ->where('source_type', InventoryLayerSourceType::GoodsReceipt)
            ->where('source_id', $purchase->id)
            ->get()
            ->keyBy(fn (InventoryLayer $layer): int => (int) $layer->source_item_id);

        $items = [];

        foreach ($purchase->items as $item) {
            $line = $this->line($item, $layers->get($item->id));

            if ($line !== null) {
                $items[] = $line;
            }
        }

        return [
            'purchase' => [
                'id' => $purchase->id,
                'po_number' => $purchase->po_number,
                'store_id' => $purchase->store_id,
                'supplier_id' => $purchase->supplier_id,
                'currency' => $purchase->currency,
                'supplier' => $purchase->supplier === null ? null : [
                    'id' => $purchase->supplier->id,
                    'name' => $purchase->supplier->name,
                ],
                'store' => $purchase->store === null ? null : [
                    'id' => $purchase->store->id,
                    'name' => $purchase->store->name,
                ],
            ],
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     purchase_order_item_id: int,
     *     product_variant_id: int,
     *     name: string,
     *     sku: string|null,
     *     image_url: string|null,
     *     unit_cost: string,
     *     purchased_quantity: string,
     *     available_quantity: string,
     *     tax_amount: string,
     *     tax_rate: string|null
     * }|null
     */
    private function line(PurchaseOrderItem $item, ?InventoryLayer $layer): ?array
    {
        $variant = $item->variant;
        $product = $variant?->product;

        if ($variant === null || $product === null || ! $product->track_inventory) {
            return null;
        }

        $name = is_string($variant->name) && $variant->name !== ''
            ? $variant->name
            : (string) $product->name;

        return [
            'purchase_order_item_id' => $item->id,
            'product_variant_id' => $variant->id,
            'name' => $name,
            'sku' => $variant->sku,
            'image_url' => $this->imageUrl($variant->image ?: $product->image),
            'unit_cost' => Decimal::normalize($item->unit_cost),
            'purchased_quantity' => Decimal::normalize($item->quantity),
            'available_quantity' => Decimal::normalize($layer?->remaining_qty ?? 0),
            'tax_amount' => Decimal::normalize($item->tax_amount),
            'tax_rate' => $this->taxRate(
                Decimal::normalize($item->quantity),
                Decimal::normalize($item->unit_cost),
                Decimal::normalize($item->tax_amount),
            ),
        ];
    }

    private function taxRate(string $quantity, string $unitCost, string $taxAmount): ?string
    {
        $gross = Decimal::mul($quantity, $unitCost);

        if (! Decimal::isPositive($gross) || ! Decimal::isPositive($taxAmount)) {
            return null;
        }

        return bcmul(bcdiv($taxAmount, $gross, 6), '100', 2);
    }

    private function imageUrl(mixed $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
