<?php

namespace DA\Inventory\Actions\StockTransfer;

use DA\Inventory\Actions\Inventory\EnsureTrackedVariant;
use DA\Inventory\DTO\StockTransfer\StockTransferDTO;
use DA\Inventory\Enums\StockTransferStatus;
use DA\Inventory\Models\StockTransfer;
use DA\Inventory\Services\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStockTransfer
{
    public function __construct(private EnsureTrackedVariant $trackedVariant) {}

    public function handle(int $id, StockTransferDTO $dto): StockTransfer
    {
        return DB::transaction(function () use ($id, $dto): StockTransfer {
            $transfer = StockTransfer::queries()->lockForTenant($id);

            if (! in_array($transfer->status, [StockTransferStatus::Draft, StockTransferStatus::Pending], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only a draft or pending transfer can be updated.',
                ]);
            }

            $this->trackedVariant->handle($dto->productVariantId);

            $transfer->product_variant_id = $dto->productVariantId;
            $transfer->from_store_id = $dto->fromStoreId;
            $transfer->to_store_id = $dto->toStoreId;
            $transfer->transfer_date = $dto->transferDate;
            $transfer->quantity = Decimal::normalize($dto->quantity);
            $transfer->notes = $dto->notes;
            $transfer->status = $dto->status === StockTransferStatus::Pending
                ? StockTransferStatus::Pending
                : StockTransferStatus::Draft;
            $transfer->save();

            return StockTransfer::queries()->findForDetail($transfer->id);
        });
    }
}
