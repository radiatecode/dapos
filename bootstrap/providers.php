<?php

use App\Providers\AppServiceProvider;
use DA\Admin\Providers\AdminServiceProvider;
use DA\Inventory\Providers\InventoryServiceProvider;

return [
    AppServiceProvider::class,
    AdminServiceProvider::class,
    InventoryServiceProvider::class,
];
