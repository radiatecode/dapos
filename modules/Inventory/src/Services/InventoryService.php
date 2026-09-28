<?php

namespace DA\Inventory\Services;

use DA\Inventory\DTO\Stock\DecreaseStockData;
use DA\Inventory\DTO\Stock\ReceiveStockData;
use DA\Inventory\DTO\Stock\StockCostResult;
use DA\Inventory\DTO\Stock\TransferStockData;
use DA\Inventory\Enums\AllocationSourceType;
use DA\Inventory\Enums\InventoryLayerSourceType;
use DA\Inventory\Enums\InventoryLayerStatus;
use DA\Inventory\Enums\StockDirection;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;
use DA\Inventory\Models\InventoryAllocation;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\InventoryLayer;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\StockMovement;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class InventoryService
{
    public function __construct(
        private CostingService $costing,
        private StockAllocationService $allocations,
        private StockMovementService $movements,
    ) {}

    public function receive(ReceiveStockData $data): InventoryLayer
    {
        return DB::transaction(function () use ($data): InventoryLayer {
            $tenantId = $this->tenantId();
            $this->assertStore($data->storeId);
            $this->assertVariant($tenantId, $data->productVariantId);
            $this->assertPositiveQuantity($data->quantity);

            InventoryBalance::queries()->lockForStoreVariant($tenantId, $data->storeId, $data->productVariantId);

            $existing = InventoryLayer::queries()->findPosted(
                $tenantId,
                $data->sourceType,
                $data->sourceId,
                $data->sourceItemId,
                $data->storeId,
                $data->productVariantId,
            );

            if ($existing instanceof InventoryLayer) {
                return $existing;
            }

            $layer = $this->createLayer(
                tenantId: $tenantId,
                storeId: $data->storeId,
                productVariantId: $data->productVariantId,
                supplierId: $data->supplierId,
                sourceType: $data->sourceType,
                sourceId: $data->sourceId,
                sourceItemId: $data->sourceItemId,
                quantity: $data->quantity,
                unitCost: $data->unitCost,
            );

            $this->movements->post(
                tenantId: $tenantId,
                storeId: $data->storeId,
                productVariantId: $data->productVariantId,
                movementType: $data->movementType,
                quantity: $data->quantity,
                unitCost: $data->unitCost,
                referenceType: $data->referenceType,
                referenceId: $data->referenceId,
                referenceItemId: $data->referenceItemId,
                direction: StockDirection::In,
                inventoryLayerId: $layer->id,
            );

            $this->recalculate($tenantId, $data->storeId, $data->productVariantId);

            return $layer;
        });
    }

    public function increase(ReceiveStockData $data): InventoryLayer
    {
        return $this->receive($data);
    }

    public function decrease(DecreaseStockData $data): StockCostResult
    {
        return DB::transaction(function () use ($data): StockCostResult {
            $tenantId = $this->tenantId();
            $this->assertStore($data->storeId);
            $this->assertVariant($tenantId, $data->productVariantId);
            $this->assertPositiveQuantity($data->quantity);
            InventoryBalance::queries()->lockForStoreVariant($tenantId, $data->storeId, $data->productVariantId);

            $existing = InventoryAllocation::queries()->forSource(
                $tenantId,
                $data->sourceType,
                $data->sourceId,
                $data->sourceItemId,
            );

            if ($existing->isNotEmpty()) {
                return $this->summarizeAllocations($existing);
            }

            return $this->costing->summarize($this->consume($data));
        });
    }

    public function transfer(TransferStockData $data): StockCostResult
    {
        return DB::transaction(function () use ($data): StockCostResult {
            $tenantId = $this->tenantId();
            $this->assertDifferentStores($data->fromStoreId, $data->toStoreId);
            $this->assertStore($data->fromStoreId);
            $this->assertStore($data->toStoreId);
            $this->assertVariant($tenantId, $data->productVariantId);
            $this->assertPositiveQuantity($data->quantity);
            $this->lockBalances($tenantId, $data->productVariantId, [$data->fromStoreId, $data->toStoreId]);

            $existing = StockMovement::queries()->forReference(
                $tenantId,
                $data->fromStoreId,
                StockReferenceType::StockTransfer,
                $data->transferId,
                StockDirection::Out,
            );

            if ($existing->isNotEmpty()) {
                return $this->costing->summarize($existing->map(fn (StockMovement $movement): array => [
                    'quantity' => Decimal::normalize($movement->quantity),
                    'total_cost' => Decimal::mul($movement->quantity, $movement->unit_cost),
                ])->all());
            }

            $slices = $this->consume(new DecreaseStockData(
                storeId: $data->fromStoreId,
                productVariantId: $data->productVariantId,
                quantity: $data->quantity,
                sourceType: AllocationSourceType::StockTransfer,
                sourceId: $data->transferId,
                sourceItemId: null,
                movementType: StockMovementType::Transfer,
                referenceType: StockReferenceType::StockTransfer,
                referenceId: $data->transferId,
                referenceItemId: null,
            ));

            foreach ($slices as $slice) {
                $layer = $this->createLayer(
                    tenantId: $tenantId,
                    storeId: $data->toStoreId,
                    productVariantId: $data->productVariantId,
                    supplierId: $slice['layer']->supplier_id,
                    sourceType: InventoryLayerSourceType::StockTransfer,
                    sourceId: $data->transferId,
                    sourceItemId: null,
                    quantity: $slice['quantity'],
                    unitCost: $slice['unit_cost'],
                );

                $this->movements->post(
                    tenantId: $tenantId,
                    storeId: $data->toStoreId,
                    productVariantId: $data->productVariantId,
                    movementType: StockMovementType::Transfer,
                    quantity: $slice['quantity'],
                    unitCost: $slice['unit_cost'],
                    referenceType: StockReferenceType::StockTransfer,
                    referenceId: $data->transferId,
                    referenceItemId: null,
                    direction: StockDirection::In,
                    inventoryLayerId: $layer->id,
                );
            }

            $this->recalculate($tenantId, $data->toStoreId, $data->productVariantId);

            return $this->costing->summarize($slices);
        });
    }

    public function reverseIncrease(ReceiveStockData $data): void
    {
        DB::transaction(function () use ($data): void {
            $tenantId = $this->tenantId();
            InventoryBalance::queries()->lockForStoreVariant($tenantId, $data->storeId, $data->productVariantId);

            $layer = InventoryLayer::queries()->findPosted(
                $tenantId,
                $data->sourceType,
                $data->sourceId,
                $data->sourceItemId,
                $data->storeId,
                $data->productVariantId,
            );

            if (! $layer instanceof InventoryLayer) {
                return;
            }

            $layer = InventoryLayer::query()->whereKey($layer->id)->lockForUpdate()->firstOrFail();

            $reversed = StockMovement::queries()->findPosted(
                $tenantId,
                $data->storeId,
                $data->referenceType,
                $data->referenceId,
                $data->referenceItemId,
                StockDirection::Out,
                $layer->id,
            );

            if ($reversed instanceof StockMovement) {
                $this->recalculate($tenantId, $data->storeId, $data->productVariantId);

                return;
            }

            if (Decimal::compare($layer->remaining_qty, $layer->received_qty) !== 0) {
                throw ValidationException::withMessages([
                    'status' => 'This stock cannot be cancelled because it has already been used.',
                ]);
            }

            $quantity = Decimal::normalize($layer->received_qty);
            $layer->remaining_qty = '0.0000';
            $layer->status = InventoryLayerStatus::Depleted;
            $layer->save();

            $this->movements->post(
                tenantId: $tenantId,
                storeId: $data->storeId,
                productVariantId: $data->productVariantId,
                movementType: $data->movementType,
                quantity: $quantity,
                unitCost: Decimal::normalize($layer->unit_cost),
                referenceType: $data->referenceType,
                referenceId: $data->referenceId,
                referenceItemId: $data->referenceItemId,
                direction: StockDirection::Out,
                inventoryLayerId: $layer->id,
            );

            $this->recalculate($tenantId, $data->storeId, $data->productVariantId);
        });
    }

    public function reverseDecrease(DecreaseStockData $data): void
    {
        DB::transaction(function () use ($data): void {
            $tenantId = $this->tenantId();
            InventoryBalance::queries()->lockForStoreVariant($tenantId, $data->storeId, $data->productVariantId);
            $this->restoreAllocations($data);
            $this->recalculate($tenantId, $data->storeId, $data->productVariantId);
        });
    }

    public function reverseTransfer(TransferStockData $data): void
    {
        DB::transaction(function () use ($data): void {
            $tenantId = $this->tenantId();
            $this->lockBalances($tenantId, $data->productVariantId, [$data->fromStoreId, $data->toStoreId]);

            $destinationLayers = InventoryLayer::queries()->lockBySource(
                $tenantId,
                InventoryLayerSourceType::StockTransfer,
                $data->transferId,
                $data->toStoreId,
            );

            foreach ($destinationLayers as $layer) {
                if ($this->movementExists($tenantId, $data->toStoreId, $data->transferId, StockDirection::Out, $layer->id)) {
                    continue;
                }

                if (Decimal::compare($layer->remaining_qty, $layer->received_qty) !== 0) {
                    throw ValidationException::withMessages([
                        'status' => 'This stock cannot be cancelled because it has already been used.',
                    ]);
                }
            }

            $this->restoreAllocations(new DecreaseStockData(
                storeId: $data->fromStoreId,
                productVariantId: $data->productVariantId,
                quantity: $data->quantity,
                sourceType: AllocationSourceType::StockTransfer,
                sourceId: $data->transferId,
                sourceItemId: null,
                movementType: StockMovementType::Transfer,
                referenceType: StockReferenceType::StockTransfer,
                referenceId: $data->transferId,
                referenceItemId: null,
            ));

            foreach ($destinationLayers as $layer) {
                if ($this->movementExists($tenantId, $data->toStoreId, $data->transferId, StockDirection::Out, $layer->id)) {
                    continue;
                }

                $quantity = Decimal::normalize($layer->received_qty);
                $unitCost = Decimal::normalize($layer->unit_cost);
                $layer->remaining_qty = '0.0000';
                $layer->status = InventoryLayerStatus::Depleted;
                $layer->save();

                $this->movements->post(
                    tenantId: $tenantId,
                    storeId: $data->toStoreId,
                    productVariantId: $data->productVariantId,
                    movementType: StockMovementType::Transfer,
                    quantity: $quantity,
                    unitCost: $unitCost,
                    referenceType: StockReferenceType::StockTransfer,
                    referenceId: $data->transferId,
                    referenceItemId: null,
                    direction: StockDirection::Out,
                    inventoryLayerId: $layer->id,
                );
            }

            $this->recalculate($tenantId, $data->fromStoreId, $data->productVariantId);
            $this->recalculate($tenantId, $data->toStoreId, $data->productVariantId);
        });
    }

    /**
     * @return list<array{layer: InventoryLayer, quantity: string, unit_cost: string, total_cost: string}>
     */
    private function consume(DecreaseStockData $data): array
    {
        $tenantId = $this->tenantId();
        $balance = InventoryBalance::queries()->lockForStoreVariant($tenantId, $data->storeId, $data->productVariantId);
        $this->assertAvailable($balance, $data->quantity);

        $layers = InventoryLayer::queries()->lockOpenForFifo($tenantId, $data->storeId, $data->productVariantId);
        $slices = $this->costing->allocate($layers, $data->quantity);

        $this->allocations->consume(
            $slices,
            $tenantId,
            $data->storeId,
            $data->productVariantId,
            $data->sourceType,
            $data->sourceId,
            $data->sourceItemId,
        );

        foreach ($slices as $slice) {
            $this->movements->post(
                tenantId: $tenantId,
                storeId: $data->storeId,
                productVariantId: $data->productVariantId,
                movementType: $data->movementType,
                quantity: $slice['quantity'],
                unitCost: $slice['unit_cost'],
                referenceType: $data->referenceType,
                referenceId: $data->referenceId,
                referenceItemId: $data->referenceItemId,
                direction: StockDirection::Out,
                inventoryLayerId: $slice['layer']->id,
            );
        }

        $this->recalculate($tenantId, $data->storeId, $data->productVariantId);

        return $slices;
    }

    private function restoreAllocations(DecreaseStockData $data): void
    {
        $tenantId = $this->tenantId();
        $allocations = InventoryAllocation::queries()->lockForSource(
            $tenantId,
            $data->sourceType,
            $data->sourceId,
            $data->sourceItemId,
        );

        foreach ($allocations as $allocation) {
            $layer = InventoryLayer::query()->whereKey($allocation->inventory_layer_id)->lockForUpdate()->firstOrFail();

            if ($this->movementExists($tenantId, $data->storeId, $data->referenceId, StockDirection::In, $layer->id, $data->referenceType, $data->referenceItemId)) {
                continue;
            }

            $layer->remaining_qty = Decimal::add($layer->remaining_qty, $allocation->quantity);
            $layer->status = InventoryLayerStatus::Open;
            $layer->save();

            $this->movements->post(
                tenantId: $tenantId,
                storeId: $data->storeId,
                productVariantId: $data->productVariantId,
                movementType: $data->movementType,
                quantity: Decimal::normalize($allocation->quantity),
                unitCost: Decimal::normalize($allocation->unit_cost),
                referenceType: $data->referenceType,
                referenceId: $data->referenceId,
                referenceItemId: $data->referenceItemId,
                direction: StockDirection::In,
                inventoryLayerId: $layer->id,
            );
        }
    }

    private function createLayer(
        int $tenantId,
        int $storeId,
        int $productVariantId,
        ?int $supplierId,
        InventoryLayerSourceType $sourceType,
        int $sourceId,
        ?int $sourceItemId,
        string $quantity,
        string $unitCost,
    ): InventoryLayer {
        $normalizedQuantity = Decimal::normalize($quantity);
        $normalizedCost = Decimal::normalize($unitCost);

        $layer = new InventoryLayer;
        $layer->tenant_id = $tenantId;
        $layer->store_id = $storeId;
        $layer->product_variant_id = $productVariantId;
        $layer->supplier_id = $supplierId;
        $layer->source_type = $sourceType;
        $layer->source_id = $sourceId;
        $layer->source_item_id = $sourceItemId;
        $layer->received_qty = $normalizedQuantity;
        $layer->remaining_qty = $normalizedQuantity;
        $layer->unit_cost = $normalizedCost;
        $layer->total_cost = Decimal::mul($normalizedQuantity, $normalizedCost);
        $layer->received_at = now();
        $layer->status = InventoryLayerStatus::Open;
        $layer->save();

        return $layer;
    }

    private function recalculate(int $tenantId, int $storeId, int $productVariantId): void
    {
        $balance = InventoryBalance::queries()->lockForStoreVariant($tenantId, $storeId, $productVariantId);
        $layers = InventoryLayer::queries()->forBalance($tenantId, $storeId, $productVariantId);
        $quantity = '0.0000';
        $value = '0.0000';

        foreach ($layers as $layer) {
            $quantity = Decimal::add($quantity, $layer->remaining_qty);
            $value = Decimal::add($value, Decimal::mul($layer->remaining_qty, $layer->unit_cost));
        }

        $available = Decimal::sub($quantity, $balance->reserved_quantity);

        if (Decimal::isNegative($available) && ! config('inventory.allow_negative_stock')) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient stock for this operation.',
            ]);
        }

        $balance->quantity = $quantity;
        $balance->available_quantity = $available;
        $balance->stock_value = $value;
        $balance->save();
    }

    /**
     * @param  Collection<int, InventoryAllocation>  $allocations
     */
    private function summarizeAllocations(Collection $allocations): StockCostResult
    {
        return $this->costing->summarize($allocations->map(fn (InventoryAllocation $allocation): array => [
            'quantity' => Decimal::normalize($allocation->quantity),
            'total_cost' => Decimal::normalize($allocation->total_cost),
        ])->all());
    }

    /**
     * @param  list<int>  $storeIds
     */
    private function lockBalances(int $tenantId, int $productVariantId, array $storeIds): void
    {
        $storeIds = array_values(array_unique($storeIds));
        sort($storeIds);

        foreach ($storeIds as $storeId) {
            InventoryBalance::queries()->lockForStoreVariant($tenantId, $storeId, $productVariantId);
        }
    }

    private function movementExists(
        int $tenantId,
        int $storeId,
        int $referenceId,
        StockDirection $direction,
        int $layerId,
        StockReferenceType $referenceType = StockReferenceType::StockTransfer,
        ?int $referenceItemId = null,
    ): bool {
        return StockMovement::queries()->findPosted(
            $tenantId,
            $storeId,
            $referenceType,
            $referenceId,
            $referenceItemId,
            $direction,
            $layerId,
        ) instanceof StockMovement;
    }

    private function assertAvailable(InventoryBalance $balance, string $quantity): void
    {
        if (config('inventory.allow_negative_stock')) {
            return;
        }

        if (Decimal::compare($balance->available_quantity, $quantity) < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient stock for this operation.',
            ]);
        }
    }

    private function assertPositiveQuantity(string $quantity): void
    {
        if (! Decimal::isPositive($quantity)) {
            throw ValidationException::withMessages([
                'quantity' => 'The quantity must be greater than zero.',
            ]);
        }
    }

    private function assertDifferentStores(int $fromStoreId, int $toStoreId): void
    {
        if ($fromStoreId === $toStoreId) {
            throw ValidationException::withMessages([
                'to_store_id' => 'The destination store must be different from the source store.',
            ]);
        }
    }

    private function assertStore(int $storeId): void
    {
        Store::queries()->findForTenant($storeId);
    }

    private function assertVariant(int $tenantId, int $productVariantId): void
    {
        $exists = ProductVariant::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($productVariantId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'The selected product variant is invalid.',
            ]);
        }
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        if (! is_int($tenantId) && ! is_numeric($tenantId)) {
            throw new RuntimeException('Tenant context is required for stock operations.');
        }

        return (int) $tenantId;
    }
}
