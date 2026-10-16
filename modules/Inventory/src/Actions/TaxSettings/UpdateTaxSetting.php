<?php

namespace DA\Inventory\Actions\TaxSettings;

use DA\Inventory\DTO\Tax\TaxSettingData;
use DA\Inventory\Models\TaxSetting;
use Illuminate\Validation\ValidationException;

class UpdateTaxSetting
{
    public static function execute(TaxSettingData $taxSettingData): TaxSetting
    {
        $setting = TaxSetting::queries()->getForTenant();

        if (! $setting->authorizeAction($setting, 'update')) {
            throw ValidationException::withMessages([
                'tax_setting' => 'You are not authorized to update this tax setting.',
            ]);
        }

        $setting->tax_mode = $taxSettingData->tax_mode;
        $setting->tax_calculation_base = $taxSettingData->tax_calculation_base;

        $setting->save();

        return $setting;
    }
}
