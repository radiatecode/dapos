<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchasePayment;
use DA\Inventory\Services\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Purchase
 */
class PurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'store_id' => $this->store_id,
            'po_number' => $this->po_number,
            'order_date' => $this->order_date?->toDateString(),
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'paid_amount' => $this->paid_amount,
            'due_amount' => $this->due_amount,
            'status' => $this->status?->value,
            'payment_status' => $this->payment_status?->value,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'creator' => $this->whenLoaded('creator', fn (): ?array => $this->creator === null ? null : [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'items_count' => $this->whenCounted('items'),
            'supplier' => SupplierResource::make($this->whenLoaded('supplier')),
            'store' => StoreResource::make($this->whenLoaded('store')),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'payments' => $this->whenLoaded('payments', function () {
                $running = '0.0000';

                return $this->payments->map(function (PurchasePayment $payment) use (&$running): array {
                    $running = Decimal::add($running, $payment->amount);

                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'reference_number' => $payment->reference_number,
                        'note' => $payment->note,
                        'paid_at' => $payment->paid_at,
                        'balance_due' => Decimal::sub($this->grand_total, $running),
                        'recorded_by' => $payment->recorded_by,
                        'recorder' => $payment->relationLoaded('recorder') && $payment->recorder !== null ? [
                            'id' => $payment->recorder->id,
                            'name' => $payment->recorder->name,
                        ] : null,
                    ];
                })->values();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
