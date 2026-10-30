<?php

use App\Providers\AppServiceProvider;
use DA\Admin\Providers\AdminServiceProvider;
use DA\Expense\Providers\ExpenseServiceProvider;
use DA\Inventory\Providers\InventoryServiceProvider;

return [
    AppServiceProvider::class,
    AdminServiceProvider::class,
    InventoryServiceProvider::class,
    ExpenseServiceProvider::class,
];
