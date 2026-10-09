<?php

namespace DA\Inventory\Actions\PurchaseReturn;

use DA\Inventory\DTO\PurchaseReturn\PurchaseReturnDTO;
use DA\Inventory\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;

class CreatePurchaseReturn
{
    public function __construct(private PurchaseReturnWriter $writer) {}

    public function handle(PurchaseReturnDTO $dto): PurchaseReturn
    {
        return DB::transaction(function () use ($dto): PurchaseReturn {
            $return = new PurchaseReturn;
            $lineTotals = $this->writer->fill($return, $dto, true);
            $return->save();
            $this->writer->syncItems($return, $dto, $lineTotals);

            return PurchaseReturn::queries()->findForDetail($return->id);
        });
    }
}
