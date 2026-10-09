<?php

namespace DA\Inventory\Actions\PurchaseReturn;

use DA\Inventory\Actions\Inventory\EnsureTrackedVariant;
use DA\Inventory\DTO\PurchaseReturn\PurchaseReturnDTO;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchaseOrderItem;
use DA\Inventory\Services\Decimal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class AssertPurchaseReturn
{
    public function __construct(private EnsureTrackedVariant $trackedVariant) {}

    /**
     * @return array{purchase: Purchase, lines: Collection<int, PurchaseOrderItem>}
     */
    public function handle(PurchaseReturnDTO $dto): array
    {
        $purchase = Purchase::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->find($dto->purchaseId);

        if (! $purchase instanceof Purchase) {
            throw ValidationException::withMessages([
                'purchase_id' => 'The selected purchase is invalid.',
            ]);
        }

        if ($purchase->status !== PurchaseStatus::Received) {
            throw ValidationException::withMessages([
                'purchase_id' => 'Only a received purchase can be returned.',
            ]);
        }

        $lines = PurchaseOrderItem::query()
            ->where('purchase_order_id', $purchase->id)
            ->get()
            ->keyBy('id');

        $seen = [];

        foreach ($dto->items as $index => $item) {
            if (isset($seen[$item->purchaseOrderItemId])) {
                throw ValidationException::withMessages([
                    "items.{$index}.purchase_order_item_id" => 'The purchase line is already on this return.',
                ]);
            }

            $seen[$item->purchaseOrderItemId] = true;
            $line = $lines->get($item->purchaseOrderItemId);

            if (! $line instanceof PurchaseOrderItem) {
                throw ValidationException::withMessages([
                    "items.{$index}.purchase_order_item_id" => 'The selected purchase line is invalid.',
                ]);
            }

            if ((int) $line->product_variant_id !== $item->productVariantId) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_variant_id" => 'The product variant does not match the purchase line.',
                ]);
            }

            if (Decimal::compare($item->quantity, $line->quantity) === 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'The return quantity cannot exceed the purchased quantity.',
                ]);
            }

            $this->trackedVariant->handle($item->productVariantId);
        }

        return [
            'purchase' => $purchase,
            'lines' => $lines,
        ];
    }
}
