<?php

namespace DA\Inventory\Actions\Tax;

use DA\Inventory\DTO\Tax\TaxData;
use DA\Inventory\Models\Tax;

class CreateTax
{
    public static function execute(TaxData $taxData): Tax
    {
        $tax = new Tax;

        $tax->tax_name = $taxData->tax_name;
        $tax->tax_type = $taxData->tax_type;
        $tax->tax_rate = $taxData->tax_rate;
        $tax->is_active = $taxData->is_active;

        $tax->save();

        return $tax;
    }
}
