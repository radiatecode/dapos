<?php

namespace DA\Inventory\Actions\Supplier;

use DA\Inventory\DTO\Supplier\SupplierDTO;
use DA\Inventory\Models\Supplier;

class UpdateSupplier
{
    public function handle(int $id, SupplierDTO $dto): Supplier
    {
        $supplier = Supplier::queries()->findForTenant($id);
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

        return $supplier->refresh();
    }
}
