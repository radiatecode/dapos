<?php

namespace DA\Inventory\Models\Queries;

use DA\Inventory\Enums\TaxCalculationBase;
use DA\Inventory\Enums\TaxMode;
use DA\Inventory\Models\TaxSetting;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaxSettingQueries extends BaseQueries
{
    public function findById($id)
    {
        return $this->eloquentBuilder()
            ->findOr(
                $id,
                fn () => throw new ModelNotFoundException('Tax setting not found for the given id.'.$id)
            );
    }

    public function getForTenant(): TaxSetting
    {
        $setting = $this->eloquentBuilder()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first();

        if ($setting) {
            return $setting;
        }

        $setting = new TaxSetting;
        $setting->tax_mode = TaxMode::ORDER_LEVEL->value;
        $setting->tax_calculation_base = TaxCalculationBase::EXCLUDE_SERVICE_CHARGES->value;
        $setting->save();

        return $setting;
    }
}
