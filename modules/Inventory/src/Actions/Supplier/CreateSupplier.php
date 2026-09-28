<?php

namespace DA\Inventory\Actions\Supplier;

use DA\Inventory\DTO\Supplier\SupplierDTO;
use DA\Inventory\Models\Supplier;

class CreateSupplier
{
    public function handle(SupplierDTO $dto): Supplier
    {
        $supplier = new Supplier;
        $supplier->tenant_id = auth()->user()->tenant_id;
        $supplier->name = $dto->name;
        $supplier->email = $dto->email;
        $supplier->phone = $dto->phone;
        $supplier->address = $dto->address;
        $supplier->city = $dto->city;
        $supplier->state = $dto->state;
        $supplier->country = $dto->country;
        $supplier->postal_code = $dto->postalCode;
        $supplier->is_active = $dto->isActive;
        $supplier->save();

        return $supplier;
    }
}
