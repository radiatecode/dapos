<?php

namespace DA\Inventory\Actions\PurchaseReturn;

use DA\Inventory\Actions\Inventory\EnsureTrackedVariant;
use DA\Inventory\Enums\InventoryLayerStatus;
use DA\Inventory\Enums\PurchaseReturnStatus;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Enums\StockDirection;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\InventoryLayer;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchaseOrderItem;
use DA\Inventory\Models\PurchaseReturn;
use DA\Inventory\Models\PurchaseReturnAllocation;
use DA\Inventory\Models\PurchaseReturnItem;
use DA\Inventory\Services\CostingService;
use DA\Inventory\Services\Decimal;
use DA\Inventory\Services\InventoryService;
use DA\Inventory\Services\StockMovementService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ApplyPurchaseReturn
{
    public function __construct(
        private EnsureTrackedVariant $trackedVariant,
        private CostingService $costing,
        private StockMovementService $movements,
        private InventoryService $inventory,
    ) {}

    public function approve(PurchaseReturn $return): void
    {
        if ($return->status === PurchaseReturnStatus::Approved) {
            return;
        }

        if ($return->status === PurchaseReturnStatus::Reject) {
            throw ValidationException::withMessages([
                'status' => 'A rejected purchase return cannot be approved.',
            ]);
        }

        if (! $return->status->isOpen()) {
            throw ValidationException::withMessages([
                'status' => 'Only a draft or pending purchase return can be approved.',
            ]);
        }

        $tenantId = (int) auth()->user()->tenant_id;
        $purchase = Purchase::query()
            ->where('tenant_id', $tenantId)
            ->find($return->purchase_id);

        if (! $purchase instanceof Purchase || $purchase->status !== PurchaseStatus::Received) {
            throw ValidationException::withMessages([
                'purchase_id' => 'Only a received purchase can be returned.',
            ]);
        }

        $return->load(['items' => fn ($query) => $query->orderBy('product_variant_id')->orderBy('id')]);

        if ($return->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'At least one purchase return item is required.',
            ]);
        }

        $lines = PurchaseOrderItem::query()
            ->whereIn('id', $return->items->pluck('purchase_order_item_id'))
            ->get()
            ->keyBy('id');

        foreach ($return->items as $item) {
            $this->assertLine($return, $item, $lines);
        }

        $requested = $this->requestedByVariant($return->items);

        foreach (array_keys($requested) as $variantId) {
            $this->trackedVariant->handle($variantId);
            $balance = InventoryBalance::queries()->lockForStoreVariant($tenantId, $return->store_id, $variantId);

            if (Decimal::compare($balance->available_quantity, $requested[$variantId]) < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Only available stock can be returned.',
                ]);
            }
        }

        foreach ($return->items as $item) {
            $this->consume($return, $item, $tenantId);
        }

        $return->status = PurchaseReturnStatus::Approved;
        $return->approval_by = auth()->id();
        $return->approval_at = now();
        $return->updated_by = auth()->id();
        $return->save();
    }

    public function reject(PurchaseReturn $return): void
    {
        if ($return->status === PurchaseReturnStatus::Reject) {
            return;
        }

        if ($return->status === PurchaseReturnStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => 'An approved purchase return cannot be rejected.',
            ]);
        }

        $return->status = PurchaseReturnStatus::Reject;
        $return->approval_by = auth()->id();
        $return->approval_at = now();
        $return->updated_by = auth()->id();
        $return->save();
    }

    /**
     * @param  Collection<int, PurchaseOrderItem>  $lines
     */
    private function assertLine(PurchaseReturn $return, PurchaseReturnItem $item, Collection $lines): void
    {
        $line = $lines->get($item->purchase_order_item_id);

        if (! $line instanceof PurchaseOrderItem || (int) $line->purchase_order_id !== (int) $return->purchase_id) {
            throw ValidationException::withMessages([
                'items' => 'The selected purchase line is invalid.',
            ]);
        }

        if ((int) $line->product_variant_id !== (int) $item->product_variant_id) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'The product variant does not match the purchase line.',
            ]);
        }

        if (Decimal::compare($item->quantity, $line->quantity) === 1) {
            throw ValidationException::withMessages([
                'quantity' => 'The return quantity cannot exceed the purchased quantity.',
            ]);
        }
    }

    /**
     * @param  Collection<int, PurchaseReturnItem>  $items
     * @return array<int, string>
     */
    private function requestedByVariant(Collection $items): array
    {
        $requested = [];

        foreach ($items as $item) {
            $variantId = (int) $item->product_variant_id;
            $requested[$variantId] = Decimal::add($requested[$variantId] ?? '0', $item->quantity);
        }

        ksort($requested);

        return $requested;
    }

    private function consume(PurchaseReturn $return, PurchaseReturnItem $item, int $tenantId): void
    {
        $layers = InventoryLayer::queries()->lockOpenForGoodsReceipt(
            $tenantId,
            $return->store_id,
            (int) $item->product_variant_id,
            (int) $return->purchase_id,
            (int) $item->purchase_order_item_id,
        );

        $onHand = '0.0000';

        foreach ($layers as $layer) {
            $onHand = Decimal::add($onHand, $layer->remaining_qty);
        }

        if (Decimal::compare($onHand, $item->quantity) < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'This purchase does not have enough remaining stock to return.',
            ]);
        }

        $slices = $this->costing->allocate($layers, Decimal::normalize($item->quantity));

        foreach ($slices as $slice) {
            $layer = $slice['layer'];
            // received_qty stays at the original receipt. A return only reduces what is still on hand.
            $layer->remaining_qty = Decimal::sub($layer->remaining_qty, $slice['quantity']);
            $layer->status = Decimal::isZero($layer->remaining_qty)
                ? InventoryLayerStatus::Depleted
                : InventoryLayerStatus::Open;
            $layer->save();
        }

        $this->inventory->recalculateBalance($return->store_id, (int) $item->product_variant_id);

        foreach ($slices as $slice) {
            $layer = $slice['layer'];

            $this->movements->post(
                tenantId: $tenantId,
                storeId: $return->store_id,
                productVariantId: (int) $item->product_variant_id,
                movementType: StockMovementType::PurchaseReturn,
                quantity: $slice['quantity'],
                unitCost: $slice['unit_cost'],
                referenceType: StockReferenceType::PurchaseReturn,
                referenceId: $return->id,
                referenceItemId: $item->id,
                direction: StockDirection::Out,
                inventoryLayerId: $layer->id,
            );

            $allocation = new PurchaseReturnAllocation;
            $allocation->tenant_id = $tenantId;
            $allocation->purchase_return_item_id = $item->id;
            $allocation->inventory_layer_id = $layer->id;
            $allocation->product_variant_id = $item->product_variant_id;
            $allocation->quantity = $slice['quantity'];
            $allocation->unit_cost = $slice['unit_cost'];
            $allocation->total_cost = $slice['total_cost'];
            $allocation->save();
        }
    }
}
