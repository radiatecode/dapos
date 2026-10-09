<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_id' => $this->sale_id,
            'customer_id' => $this->customer_id,
            'payment_method_id' => $this->payment_method_id,
            'bill_amount' => $this->bill_amount,
            'received_amount' => $this->received_amount,
            'paid_amount' => $this->paid_amount,
            'due_amount' => $this->due_amount,
            'returned_amount' => $this->returned_amount,
            'reference_number' => $this->reference_number,
            'paid_at' => $this->paid_at,
            'received_by' => $this->received_by,
            'payment_method' => $this->whenLoaded('paymentMethod', fn (): array => [
                'id' => $this->paymentMethod->id,
                'name' => $this->paymentMethod->name,
            ]),
            'receiver' => $this->whenLoaded('receiver', fn (): ?array => $this->receiver === null ? null : [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
