<?php

namespace DA\Inventory\Actions\PurchaseReturn;

use DA\Inventory\DTO\PurchaseReturn\PurchaseReturnDTO;
use DA\Inventory\Enums\PurchaseReturnStatus;
use DA\Inventory\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePurchaseReturn
{
    public function __construct(private PurchaseReturnWriter $writer) {}

    public function handle(int $id, PurchaseReturnDTO $dto): PurchaseReturn
    {
        return DB::transaction(function () use ($id, $dto): PurchaseReturn {
            $return = PurchaseReturn::queries()->lockForTenant($id);

            if (! $return->authorizeAction($return, 'update')) {
                throw ValidationException::withMessages([
                    'purchase_return' => 'You are not authorized to update this purchase return.',
                ]);
            }

            if (! $return->status->isOpen()) {
                $message = $return->status === PurchaseReturnStatus::Approved
                    ? 'An approved purchase return cannot be updated.'
                    : 'A rejected purchase return cannot be updated.';

                throw ValidationException::withMessages([
                    'status' => $message,
                ]);
            }

            $lineTotals = $this->writer->fill($return, $dto, false);
            $return->save();
            $this->writer->syncItems($return, $dto, $lineTotals);

            return PurchaseReturn::queries()->findForDetail($return->id);
        });
    }
}
