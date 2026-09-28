<?php

namespace DA\Inventory\Actions\Purchase;

use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePurchaseStatus
{
    public function __construct(private ReceivePurchaseStock $receiveStock) {}

    public function handle(int $id, PurchaseStatus $status): Purchase
    {
        return DB::transaction(function () use ($id, $status): Purchase {
            $purchase = Purchase::queries()->findForTenant($id);
            $purchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($purchase->status === PurchaseStatus::Received && $status !== PurchaseStatus::Received) {
                throw ValidationException::withMessages([
                    'status' => 'A received purchase cannot change status.',
                ]);
            }

            $purchase->status = $status;
            $purchase->updated_by = auth()->id();
            $purchase->save();

            // TODO: in future may be there will be a way to receive stock from a purchase by anothe good receipt
            // option by GRN. One purchase can have multiple good receipts.
            if ($status === PurchaseStatus::Received) {
                $this->receiveStock->handle($purchase);
            }

            return Purchase::queries()->findForDetail($purchase->id);
        });
    }
}
