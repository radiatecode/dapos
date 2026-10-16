<?php

namespace DA\Inventory\Actions\Customer;

use DA\Inventory\Models\Customer;
use Illuminate\Validation\ValidationException;

class DeleteCustomer
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            $customer = Customer::queries()->findForTenant((int) $id);

            if (! $customer->authorizeAction($customer, 'delete')) {
                throw ValidationException::withMessages([
                    'customer' => 'You are not authorized to delete this customer.',
                ]);
            }

            $customer->delete();
        }
    }
}
