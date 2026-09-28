<?php

namespace DA\Inventory\Actions\Store;

use DA\Inventory\DTO\Store\StoreDTO;
use DA\Inventory\Models\Store;

class CreateStore
{
    public function handle(StoreDTO $dto): Store
    {
        $store = new Store;
        $store->tenant_id = auth()->user()->tenant_id;
        $store->name = $dto->name;
        $store->address = $dto->address;
        $store->is_active = $dto->isActive;
        $store->save();

        return $store;
    }
}
