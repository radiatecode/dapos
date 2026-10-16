<?php

namespace DA\Inventory\Actions\PurchaseReturn;

use DA\Inventory\Enums\PurchaseReturnStatus;
use DA\Inventory\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePurchaseReturnStatus
{
    public function __construct(private ApplyPurchaseReturn $apply) {}

    public function handle(int $id, PurchaseReturnStatus $status): PurchaseReturn
    {
        return DB::transaction(function () use ($id, $status): PurchaseReturn {
            $return = PurchaseReturn::queries()->lockForTenant($id);

            if (! $return->authorizeAction($return, 'update')) {
                throw ValidationException::withMessages([
                    'purchase_return' => 'You are not authorized to update this purchase return.',
                ]);
            }

            if ($status === PurchaseReturnStatus::Approved) {
                $this->apply->approve($return);
            } elseif ($status === PurchaseReturnStatus::Reject) {
                $this->apply->reject($return);
            } else {
                throw ValidationException::withMessages([
                    'status' => 'The selected status is invalid.',
                ]);
            }

            return PurchaseReturn::queries()->findForDetail($return->id);
        });
    }
}
