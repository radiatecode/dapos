<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\CurrencySettings\UpdateCurrencySetting;
use DA\Inventory\Http\Requests\Api\V1\Currency\CurrencySettingRequest;
use DA\Inventory\Http\Resources\Api\V1\CurrencySettingResource;
use DA\Inventory\Models\CurrencySetting;

class CurrencySettingController extends Controller
{
    public function show(): CurrencySettingResource
    {
        return CurrencySettingResource::make(
            CurrencySetting::queries()->getForTenant(),
        );
    }

    public function update(CurrencySettingRequest $request, UpdateCurrencySetting $update): CurrencySettingResource
    {
        return CurrencySettingResource::make(
            $update->handle($request->toDTO()),
        );
    }
}
