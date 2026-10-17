<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\QuickSale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuickSale
 */
class QuickSaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'invoice_number' => $this->invoice_number,
            'sale_date' => $this->sale_date?->toDateString(),
            'total_items' => $this->total_items,
            'grand_total' => $this->grand_total,
            'payment_method_id' => $this->payment_method?->value,
            'payment_method' => $this->payment_method?->label(),
            'received_amount' => $this->received_amount,
            'change_amount' => $this->change_amount,
            'reference_number' => $this->reference_number,
            'note' => $this->note,
            'cashier_id' => $this->cashier_id,
            'store' => $this->whenLoaded('store', fn () => StoreResource::make($this->store)),
            'cashier' => $this->whenLoaded('cashier', fn (): ?array => $this->cashier === null ? null : [
                'id' => $this->cashier->id,
                'name' => $this->cashier->name,
            ]),
            'items' => QuickSaleItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
