<?php

namespace DA\Inventory\Actions\CurrencySettings;

use DA\Inventory\DTO\Currency\CurrencySettingDTO;
use DA\Inventory\Models\CurrencySetting;

class UpdateCurrencySetting
{
    public function handle(CurrencySettingDTO $dto): CurrencySetting
    {
        $setting = CurrencySetting::queries()->findForTenant() ?? new CurrencySetting;
        $setting->tenant_id = auth()->user()->tenant_id;
        $setting->currency_code = $dto->currencyCode;
        $setting->currency_symbol = $dto->currencySymbol;
        $setting->decimal_places = $dto->decimalPlaces;
        $setting->decimal_separator = $dto->decimalSeparator;
        $setting->thousands_separator = $dto->thousandsSeparator;
        $setting->symbol_position = $dto->symbolPosition;
        $setting->show_decimals = $dto->showDecimals;
        $setting->add_space = $dto->addSpace;
        $setting->save();

        return $setting;
    }
}
