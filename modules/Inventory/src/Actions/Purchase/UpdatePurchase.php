<?php

namespace DA\Inventory\Actions\Purchase;

use DA\Inventory\DTO\Purchase\PurchaseDTO;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePurchase
{
    public function __construct(
        private PurchaseWriter $writer,
        private ReceivePurchaseStock $receiveStock,
    ) {}

    public function handle(int $id, PurchaseDTO $dto): Purchase
    {
        return DB::transaction(function () use ($id, $dto): Purchase {
            $purchase = Purchase::queries()->findForTenant($id);
            $purchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($purchase->status === PurchaseStatus::Received) {
                throw ValidationException::withMessages([
                    'status' => 'A received purchase cannot be updated.',
                ]);
            }

            $lineTotals = $this->writer->fill($purchase, $dto, false);
            $purchase->save();
            $this->writer->syncItems($purchase, $dto, $lineTotals);
            $purchase->refresh();
            $this->receiveStock->handle($purchase);

            return Purchase::queries()->findForDetail($purchase->id);
        });
    }
}
