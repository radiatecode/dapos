<?php

namespace DA\Inventory\Actions\StockTransfer;

use DA\Inventory\Enums\StockTransferStatus;
use DA\Inventory\Models\StockTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStockTransferStatus
{
    public function __construct(private ApplyStockTransfer $apply) {}

    public function handle(int $id, StockTransferStatus $status): StockTransfer
    {
        return DB::transaction(function () use ($id, $status): StockTransfer {
            $transfer = StockTransfer::queries()->lockForTenant($id);

            if ($status === StockTransferStatus::Completed) {
                $this->apply->complete($transfer);
            } elseif ($status === StockTransferStatus::Cancelled) {
                $this->apply->cancel($transfer);
            } elseif ($status === StockTransferStatus::Pending && $transfer->status === StockTransferStatus::Draft) {
                $transfer->status = StockTransferStatus::Pending;
                $transfer->save();
            } elseif ($status === StockTransferStatus::Pending && $transfer->status === StockTransferStatus::Pending) {
                // Pending does not move stock. Repeating it is a no-op.
            } else {
                throw ValidationException::withMessages([
                    'status' => 'The selected status is invalid.',
                ]);
            }

            return StockTransfer::queries()->findForDetail($transfer->id);
        });
    }
}
