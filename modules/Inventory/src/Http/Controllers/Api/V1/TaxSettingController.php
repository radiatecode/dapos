<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\TaxSettings\UpdateTaxSetting;
use DA\Inventory\Http\Requests\Api\V1\Tax\TaxSettingRequest;
use DA\Inventory\Http\Resources\Api\V1\TaxSettingResource;
use DA\Inventory\Models\TaxSetting;

class TaxSettingController extends Controller
{
    public function show()
    {
        return TaxSettingResource::make(
            TaxSetting::queries()->getForTenant()
        );
    }

    public function update(TaxSettingRequest $request)
    {
        $setting = UpdateTaxSetting::execute($request->toData());

        return TaxSettingResource::make($setting);
    }
}
