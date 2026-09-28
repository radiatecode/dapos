<?php

namespace DA\Inventory\Actions\StockTransfer;

use DA\Inventory\Actions\Inventory\EnsureTrackedVariant;
use DA\Inventory\DTO\StockTransfer\StockTransferDTO;
use DA\Inventory\Enums\StockTransferStatus;
use DA\Inventory\Models\StockTransfer;
use DA\Inventory\Services\Decimal;
use Illuminate\Support\Facades\DB;

class CreateStockTransfer
{
    public function __construct(
        private EnsureTrackedVariant $trackedVariant,
        private ApplyStockTransfer $apply,
    ) {}

    public function handle(StockTransferDTO $dto): StockTransfer
    {
        return DB::transaction(function () use ($dto): StockTransfer {
            $this->trackedVariant->handle($dto->productVariantId);

            $transfer = new StockTransfer;
            $transfer->tenant_id = auth()->user()->tenant_id;
            $transfer->product_variant_id = $dto->productVariantId;
            $transfer->reference_number = StockTransfer::queries()->nextReferenceNumber();
            $transfer->from_store_id = $dto->fromStoreId;
            $transfer->to_store_id = $dto->toStoreId;
            $transfer->transfer_date = $dto->transferDate;
            $transfer->quantity = Decimal::normalize($dto->quantity);
            $transfer->notes = $dto->notes;
            $transfer->status = $dto->status === StockTransferStatus::Pending
                ? StockTransferStatus::Pending
                : StockTransferStatus::Draft;
            $transfer->created_by = auth()->id();
            $transfer->save();

            if ($dto->status === StockTransferStatus::Completed) {
                $locked = StockTransfer::queries()->lockForTenant($transfer->id);
                $this->apply->complete($locked);
            }

            return StockTransfer::queries()->findForDetail($transfer->id);
        });
    }
}
