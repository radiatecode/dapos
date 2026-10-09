<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Enums\TaxType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->tax_name,
            'tax_type' => $this->tax_type,
            'tax_type_label' => TaxType::from($this->tax_type)->label(),
            'tax_rate' => (float) $this->tax_rate,
            'is_active' => (bool) $this->is_active,
            'created_at' => _time($this->created_at)->toDateTimeString(),
            'updated_at' => _time($this->updated_at)->toDateTimeString(),
            'creator' => $this->creator_name ?? null,
            'updater' => $this->updater_name ?? null,
        ];
    }
}
