<?php

namespace DA\Inventory\Actions\Store;

use DA\Inventory\DTO\Store\StoreDTO;
use DA\Inventory\Models\Store;

class UpdateStore
{
    public function handle(int $id, StoreDTO $dto): Store
    {
        $store = Store::queries()->findForTenant($id);
        $store->name = $dto->name;
        $store->address = $dto->address;
        $store->is_active = $dto->isActive;
        $store->save();

        return $store->refresh();
    }
}
