<?php

namespace DA\Inventory\Models\Queries;

use DA\Admin\Models\Currency;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\DecimalSeparator;
use DA\Inventory\Enums\SymbolPosition;
use DA\Inventory\Enums\ThousandsSeparator;
use DA\Inventory\Models\CurrencySetting;
use Illuminate\Database\Eloquent\Builder;

class CurrencySettingQueries extends BaseQueries
{
    public function findForTenant(): ?CurrencySetting
    {
        $setting = $this->forAuthenticatedTenant()->first();

        return $setting instanceof CurrencySetting ? $setting : null;
    }

    public function getForTenant(): CurrencySetting
    {
        $existing = $this->findForTenant();

        if ($existing instanceof CurrencySetting) {
            return $existing;
        }

        $currency = $this->resolveDefaultCurrency();

        $setting = new CurrencySetting;
        $setting->tenant_id = auth()->user()->tenant_id;
        $setting->currency_code = $currency->code;
        $setting->currency_symbol = $currency->symbol;
        $setting->decimal_places = 2;
        $setting->decimal_separator = DecimalSeparator::DOT;
        $setting->thousands_separator = ThousandsSeparator::COMMA;
        $setting->symbol_position = SymbolPosition::LEFT;
        $setting->show_decimals = true;
        $setting->add_space = false;
        $setting->save();

        return $setting;
    }

    private function resolveDefaultCurrency(): Currency
    {
        $tenant = Tenant::query()->findOrFail(auth()->user()->tenant_id);
        $preferredCode = strtoupper((string) $tenant->currency);

        $preferred = Currency::query()
            ->where('is_active', true)
            ->where('code', $preferredCode)
            ->first();

        if ($preferred instanceof Currency) {
            return $preferred;
        }

        $fallback = Currency::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->first();

        if ($fallback instanceof Currency) {
            return $fallback;
        }

        abort(422, 'No active currency is available to configure.');
    }

    private function forAuthenticatedTenant(): Builder
    {
        return $this->eloquentBuilder()
            ->where('tenant_id', auth()->user()->tenant_id);
    }
}
