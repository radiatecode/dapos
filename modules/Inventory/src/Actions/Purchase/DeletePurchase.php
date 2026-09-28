<?php

namespace DA\Inventory\Actions\Purchase;

use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeletePurchase
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            foreach ($ids as $id) {
                $purchase = Purchase::queries()->findForTenant((int) $id);
                $purchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

                if ($purchase->status === PurchaseStatus::Received) {
                    throw ValidationException::withMessages([
                        'ids' => 'A received purchase cannot be deleted.',
                    ]);
                }

                $purchase->delete();
            }
        });
    }
}
