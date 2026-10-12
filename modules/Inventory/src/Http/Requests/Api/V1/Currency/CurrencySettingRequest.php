<?php

namespace DA\Inventory\Http\Requests\Api\V1\Currency;

use DA\Admin\Models\Currency;
use DA\Inventory\DTO\Currency\CurrencySettingDTO;
use DA\Inventory\Enums\DecimalSeparator;
use DA\Inventory\Enums\SymbolPosition;
use DA\Inventory\Enums\ThousandsSeparator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurrencySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'currency_code' => [
                'required',
                'string',
                'size:3',
                Rule::exists(Currency::class, 'code')->where('is_active', true),
            ],
            'currency_symbol' => ['required', 'string', 'max:16'],
            'decimal_places' => ['required', 'integer', Rule::in([0, 1, 2, 3, 4])],
            'decimal_separator' => ['required', 'string', Rule::enum(DecimalSeparator::class)],
            'thousands_separator' => ['required', 'string', Rule::enum(ThousandsSeparator::class)],
            'symbol_position' => ['required', 'string', Rule::enum(SymbolPosition::class)],
            'show_decimals' => ['required', 'boolean'],
            'add_space' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['decimal_separator', 'thousands_separator'])) {
                    return;
                }

                $decimalSeparator = $this->input('decimal_separator');
                $thousandsSeparator = $this->input('thousands_separator');

                if ($thousandsSeparator !== ThousandsSeparator::NONE->value && $decimalSeparator === $thousandsSeparator) {
                    $validator->errors()->add(
                        'thousands_separator',
                        'The thousands separator must be different from the decimal separator.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('currency_code') || ! is_string($this->input('currency_code'))) {
            return;
        }

        $this->merge([
            'currency_code' => strtoupper(trim($this->string('currency_code')->toString())),
        ]);
    }

    public function toDTO(): CurrencySettingDTO
    {
        $data = $this->validated();

        return new CurrencySettingDTO(
            currencyCode: $data['currency_code'],
            currencySymbol: $data['currency_symbol'],
            decimalPlaces: $data['decimal_places'],
            decimalSeparator: DecimalSeparator::from($data['decimal_separator']),
            thousandsSeparator: ThousandsSeparator::from($data['thousands_separator']),
            symbolPosition: SymbolPosition::from($data['symbol_position']),
            showDecimals: (bool) $data['show_decimals'],
            addSpace: (bool) $data['add_space'],
        );
    }
}
