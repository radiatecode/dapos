<?php

namespace DA\Inventory\Actions\Store;

use DA\Inventory\DTO\Store\StoreDTO;
use DA\Inventory\Models\Store;
use Illuminate\Validation\ValidationException;

class UpdateStore
{
    public function handle(int $id, StoreDTO $dto): Store
    {
        $store = Store::queries()->findForTenant($id);

        if (! $store->authorizeAction($store, 'update')) {
            throw ValidationException::withMessages([
                'store' => 'You are not authorized to update this store.',
            ]);
        }

        $store->name = $dto->name;
        $store->address = $dto->address;
        $store->is_active = $dto->isActive;
        $store->save();

        return $store->refresh();
    }
}
