<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\QuickSaleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuickSaleItem
 */
class QuickSaleItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'unit_amount' => $this->unit_amount,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'sort_order' => $this->sort_order,
        ];
    }
}
