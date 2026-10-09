<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Enums\TaxCalculationBase;
use DA\Inventory\Enums\TaxMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tax_mode' => $this->tax_mode,
            'tax_mode_label' => TaxMode::from($this->tax_mode)->label(),
            'tax_calculation_base' => $this->tax_calculation_base,
            'tax_calculation_base_label' => TaxCalculationBase::from($this->tax_calculation_base)->label(),
            'created_at' => _time($this->created_at)->toDateTimeString(),
            'updated_at' => _time($this->updated_at)->toDateTimeString(),
        ];
    }
}
