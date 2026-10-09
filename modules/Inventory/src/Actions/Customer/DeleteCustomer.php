<?php

namespace DA\Inventory\Actions\Customer;

use DA\Inventory\Models\Customer;

class DeleteCustomer
{
    /**
     * @param  list<int>  $ids
     */
    public function handle(array $ids): void
    {
        foreach ($ids as $id) {
            Customer::queries()->findForTenant((int) $id)->delete();
        }
    }
}
