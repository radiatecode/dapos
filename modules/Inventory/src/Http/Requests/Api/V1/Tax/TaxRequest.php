<?php

namespace DA\Inventory\Http\Requests\Api\V1\Tax;

use DA\Inventory\DTO\Tax\TaxData;
use DA\Inventory\Enums\TaxType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'tax_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('taxes')
                    ->where('tenant_id', auth()->user()->tenant_id),
            ],
            'tax_type' => [
                'required',
                'integer',
                Rule::in(array_column(TaxType::cases(), 'value')),
            ],
            'tax_rate' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];

        if ($this->isMethod('PUT')) {
            $rules['tax_name'] = [
                'required',
                'string',
                'max:150',
                Rule::unique('taxes')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->ignore($this->id),
            ];
        }

        return $rules;
    }

    public function toData(): TaxData
    {
        $data = $this->validated();

        if ($this->isMethod('PUT')) {
            $data['id'] = $this->id;
        }

        return new TaxData(
            tax_name: $data['tax_name'],
            tax_type: $data['tax_type'],
            tax_rate: (float) $data['tax_rate'],
            is_active: $data['is_active'] ?? true,
            id: $data['id'] ?? null,
        );
    }
}
