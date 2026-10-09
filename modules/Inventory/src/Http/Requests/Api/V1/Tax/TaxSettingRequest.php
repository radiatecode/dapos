<?php

namespace DA\Inventory\Http\Requests\Api\V1\Tax;

use DA\Inventory\DTO\Tax\TaxSettingData;
use DA\Inventory\Enums\TaxCalculationBase;
use DA\Inventory\Enums\TaxMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tax_mode' => [
                'required',
                'integer',
                Rule::in(array_column(TaxMode::cases(), 'value')),
            ],
            'tax_calculation_base' => [
                'required',
                'integer',
                Rule::in(array_column(TaxCalculationBase::cases(), 'value')),
            ],
        ];
    }

    public function toData(): TaxSettingData
    {
        $data = $this->validated();

        return new TaxSettingData(
            tax_mode: $data['tax_mode'],
            tax_calculation_base: $data['tax_calculation_base'],
        );
    }
}
