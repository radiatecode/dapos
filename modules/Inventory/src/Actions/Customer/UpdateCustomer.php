<?php

namespace DA\Inventory\Actions\Customer;

use DA\Inventory\DTO\Customer\CustomerDTO;
use DA\Inventory\Models\Customer;
use Illuminate\Validation\ValidationException;

class UpdateCustomer
{
    public function handle(int $id, CustomerDTO $dto): Customer
    {
        $customer = Customer::queries()->findForTenant($id);

        if (! $customer->authorizeAction($customer, 'update')) {
            throw ValidationException::withMessages([
                'customer' => 'You are not authorized to update this customer.',
            ]);
        }

        $customer->name = $dto->name;
        $customer->phone = $dto->phone;
        $customer->email = $dto->email;
        $customer->address = $dto->address;
        $customer->updated_by = auth()->id();
        $customer->save();

        return $customer->refresh();
    }
}
