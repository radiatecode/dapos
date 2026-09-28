<?php

namespace DA\Inventory\Actions\StockTransfer;

use DA\Inventory\Actions\Inventory\EnsureTrackedVariant;
use DA\Inventory\DTO\Stock\TransferStockData;
use DA\Inventory\Enums\StockTransferStatus;
use DA\Inventory\Models\StockTransfer;
use DA\Inventory\Models\Store;
use DA\Inventory\Services\Decimal;
use DA\Inventory\Services\InventoryService;
use Illuminate\Validation\ValidationException;

class ApplyStockTransfer
{
    public function __construct(
        private InventoryService $inventory,
        private EnsureTrackedVariant $trackedVariant,
    ) {}

    public function complete(StockTransfer $transfer): void
    {
        if ($transfer->status === StockTransferStatus::Completed) {
            return;
        }

        if (! in_array($transfer->status, [StockTransferStatus::Draft, StockTransferStatus::Pending], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only a draft or pending transfer can be completed.',
            ]);
        }

        $this->trackedVariant->handle($transfer->product_variant_id);
        $this->assertActiveStore($transfer->from_store_id, 'from_store_id');
        $this->assertActiveStore($transfer->to_store_id, 'to_store_id');

        if ($transfer->from_store_id === $transfer->to_store_id) {
            throw ValidationException::withMessages([
                'to_store_id' => 'The destination store must be different from the source store.',
            ]);
        }

        $result = $this->inventory->transfer(new TransferStockData(
            fromStoreId: $transfer->from_store_id,
            toStoreId: $transfer->to_store_id,
            productVariantId: $transfer->product_variant_id,
            quantity: Decimal::normalize($transfer->quantity),
            transferId: $transfer->id,
        ));

        $transfer->unit_cost = $result->unitCost;
        $transfer->total_cost = $result->totalCost;
        $transfer->status = StockTransferStatus::Completed;
        $transfer->save();
    }

    public function cancel(StockTransfer $transfer): void
    {
        if ($transfer->status === StockTransferStatus::Cancelled) {
            return;
        }

        if (in_array($transfer->status, [StockTransferStatus::Draft, StockTransferStatus::Pending], true)) {
            $transfer->status = StockTransferStatus::Cancelled;
            $transfer->save();

            return;
        }

        $this->inventory->reverseTransfer(new TransferStockData(
            fromStoreId: $transfer->from_store_id,
            toStoreId: $transfer->to_store_id,
            productVariantId: $transfer->product_variant_id,
            quantity: Decimal::normalize($transfer->quantity),
            transferId: $transfer->id,
        ));

        $transfer->status = StockTransferStatus::Cancelled;
        $transfer->save();
    }

    private function assertActiveStore(int $storeId, string $field): void
    {
        $store = Store::queries()->findForTenant($storeId);

        if (! $store->is_active) {
            throw ValidationException::withMessages([
                $field => 'The selected store is inactive.',
            ]);
        }
    }
}
