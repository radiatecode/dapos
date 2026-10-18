<?php

namespace DA\Inventory\Actions\Purchase;

use DA\Inventory\DTO\Purchase\PurchaseDTO;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Services\Decimal;
use Illuminate\Support\Facades\DB;

class CreatePurchase
{
    public function __construct(
        private PurchaseWriter $writer,
        private PurchasePaymentWriter $payments,
        private ReceivePurchaseStock $receiveStock,
    ) {}

    public function handle(PurchaseDTO $dto): Purchase
    {
        return DB::transaction(function () use ($dto): Purchase {
            $purchase = new Purchase;
            $lineTotals = $this->writer->fill($purchase, $dto, true);
            $purchase->save();
            $this->writer->syncItems($purchase, $dto, $lineTotals);

            if (Decimal::isPositive($purchase->paid_amount)) {
                $this->payments->create($purchase, Decimal::normalize($purchase->paid_amount), null, null);
            }

            $this->receiveStock->handle($purchase);

            return Purchase::queries()->findForDetail($purchase->id);
        });
    }
}
