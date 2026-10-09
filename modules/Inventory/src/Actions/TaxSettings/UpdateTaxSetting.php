<?php

namespace DA\Inventory\Actions\TaxSettings;

use DA\Inventory\DTO\Tax\TaxSettingData;
use DA\Inventory\Models\TaxSetting;

class UpdateTaxSetting
{
    public static function execute(TaxSettingData $taxSettingData): TaxSetting
    {
        $setting = TaxSetting::queries()->getForTenant();

        $setting->tax_mode = $taxSettingData->tax_mode;
        $setting->tax_calculation_base = $taxSettingData->tax_calculation_base;

        $setting->save();

        return $setting;
    }
}
