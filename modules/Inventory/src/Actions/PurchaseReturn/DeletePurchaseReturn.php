<?php

namespace DA\Inventory\Actions\PurchaseReturn;

use DA\Inventory\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeletePurchaseReturn
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(array $ids): void
    {
        $ids = array_values(array_unique(array_map(intval(...), $ids)));
        sort($ids);

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $id) {
                $return = PurchaseReturn::queries()->lockForTenant($id);

                if (! $return->authorizeAction($return, 'delete')) {
                    throw ValidationException::withMessages([
                        'purchase_return' => 'You are not authorized to delete this purchase return.',
                    ]);
                }

                if (! $return->status->isOpen()) {
                    throw ValidationException::withMessages([
                        'ids' => 'Only a draft or pending purchase return can be deleted.',
                    ]);
                }

                $return->delete();
            }
        });
    }
}
