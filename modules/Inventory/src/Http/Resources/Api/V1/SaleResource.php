<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sale
 */
class SaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'invoice_number' => $this->invoice_number,
            'sale_date' => $this->sale_date?->toDateString(),
            'total_items' => $this->total_items,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'shipping_amount' => $this->shipping_amount,
            'rounding_amount' => $this->rounding_amount,
            'grand_total' => $this->grand_total,
            'paid_amount' => $this->paid_amount,
            'due_amount' => $this->due_amount,
            'status' => $this->status?->value,
            'payment_status' => $this->payment_status?->value,
            'cashier_id' => $this->cashier_id,
            'items_count' => $this->whenCounted('items'),
            'customer' => $this->whenLoaded('customer', fn () => CustomerResource::make($this->customer)),
            'store' => $this->whenLoaded('store', fn () => StoreResource::make($this->store)),
            'cashier' => $this->whenLoaded('cashier', fn (): ?array => $this->cashier === null ? null : [
                'id' => $this->cashier->id,
                'name' => $this->cashier->name,
            ]),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
