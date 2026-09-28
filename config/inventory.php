<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allow Negative Stock
    |--------------------------------------------------------------------------
    |
    | Decreases fail when available quantity is lower than the requested
    | quantity. Set this to true only when negative available stock is an
    | explicit operational choice. Layers still cannot be consumed past zero.
    |
    */

    'allow_negative_stock' => false,

];
