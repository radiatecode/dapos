<?php

namespace DA\Inventory\Actions\Customer;

use DA\Inventory\DTO\Customer\CustomerDTO;
use DA\Inventory\Models\Customer;

class UpdateCustomer
{
    public function handle(int $id, CustomerDTO $dto): Customer
    {
        $customer = Customer::queries()->findForTenant($id);
        $customer->name = $dto->name;
        $customer->phone = $dto->phone;
        $customer->email = $dto->email;
        $customer->address = $dto->address;
        $customer->updated_by = auth()->id();
        $customer->save();

        return $customer->refresh();
    }
}
