<?php

namespace DA\Inventory\Actions\Customer;

use DA\Inventory\DTO\Customer\CustomerDTO;
use DA\Inventory\Models\Customer;

class CreateCustomer
{
    public function handle(CustomerDTO $dto): Customer
    {
        $userId = auth()->id();

        $customer = new Customer;
        $customer->tenant_id = auth()->user()->tenant_id;
        $customer->name = $dto->name;
        $customer->phone = $dto->phone;
        $customer->email = $dto->email;
        $customer->address = $dto->address;
        $customer->created_by = $userId;
        $customer->updated_by = $userId;
        $customer->save();

        return $customer;
    }
}
