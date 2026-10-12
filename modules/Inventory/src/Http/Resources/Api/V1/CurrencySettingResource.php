<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\CurrencySetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CurrencySetting
 */
class CurrencySettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'currency_code' => $this->currency_code,
            'currency_symbol' => $this->currency_symbol,
            'decimal_places' => $this->decimal_places,
            'decimal_separator' => $this->decimal_separator->value,
            'decimal_separator_label' => $this->decimal_separator->label(),
            'decimal_separator_character' => $this->decimal_separator->character(),
            'thousands_separator' => $this->thousands_separator->value,
            'thousands_separator_label' => $this->thousands_separator->label(),
            'thousands_separator_character' => $this->thousands_separator->character(),
            'symbol_position' => $this->symbol_position->value,
            'symbol_position_label' => $this->symbol_position->label(),
            'show_decimals' => $this->show_decimals,
            'add_space' => $this->add_space,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
